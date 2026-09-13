<?php

namespace App\Jobs;

use App\Models\FormPengujian;
use App\Models\FormVerification;
use App\Services\GoogleDocsService;
use App\Services\NotificationService;
use App\Enums\Role;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateLhpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private int $formId,
        private string $divisiName,
        private int $divisiUserId,
    ) {}

    public function handle(): void
    {
        $form = FormPengujian::findOrFail($this->formId);

        if ($form->lhp_google_file_id) {
            $form->update(['lhp_generation_status' => 'completed']);
            return;
        }

        $form->update([
            'lhp_generation_status' => 'processing',
            'lhp_generation_started_at' => now(),
            'lhp_generation_attempts' => $form->lhp_generation_attempts + 1,
        ]);

        $googleDocsService = new GoogleDocsService();
        $lhpResult = $googleDocsService->generateLhp($form, $this->divisiName);

        $form->update([
            'lhp_google_file_id' => $lhpResult['id'],
            'lhp_uploaded_at' => now(),
            'lhp_signed_divisi_at' => now(),
            'status' => 'ttd_upa',
            'lhp_generation_status' => 'completed',
            'lhp_generation_error' => null,
        ]);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'approve',
            'from_status' => 'menunggu_review_divisi',
            'to_status' => 'ttd_upa',
            'verified_by' => $this->divisiUserId,
        ]);

        $notificationService = new NotificationService();
        $notificationService->notifyTtdRequest($form, Role::KEPALA_UPA);
    }

    public function failed(\Throwable $e): void
    {
        $form = FormPengujian::find($this->formId);
        if (!$form) return;

        $form->update([
            'lhp_generation_status' => 'failed',
            'lhp_generation_error' => $e->getMessage(),
        ]);

        Log::error("GenerateLhpJob permanently failed for form {$this->formId}: " . $e->getMessage());

        $notificationService = new NotificationService();
        $notificationService->create(
            $this->divisiUserId,
            'lhp_failed',
            'Generate LHP Gagal',
            "LHP untuk form {$form->form_number} gagal di-generate setelah beberapa percobaan: " . $e->getMessage(),
            ['form_id' => $this->formId]
        );
    }
}
