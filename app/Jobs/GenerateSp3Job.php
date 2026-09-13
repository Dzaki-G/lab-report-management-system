<?php

namespace App\Jobs;

use App\Models\Sp3Document;
use App\Services\GoogleDocsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSp3Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [15, 30, 60];

    public function __construct(private int $sp3Id) {}

    public function handle(): void
    {
        $sp3 = Sp3Document::with(['parameter', 'samples', 'form'])->findOrFail($this->sp3Id);

        if ($sp3->google_doc_id) {
            $sp3->update(['doc_generation_status' => 'completed']);
            return;
        }

        $sp3->update(['doc_generation_status' => 'processing']);

        $samples = $sp3->samples->map(fn ($s) => [
            'sample_id'   => $s->id,
            'sample_code' => $s->sample_code,
            'sample_name' => $s->sample_name,
        ])->toArray();

        $googleDocsService = new GoogleDocsService();

        $result = $googleDocsService->generateSp3WithTable(
            $sp3->sp3_number,
            $samples,
            $sp3->parameter->name ?? '',
            $googleDocsService->generatePerihal($sp3->form),
            $sp3->no_sppp,
            $sp3->ik
        );

        $sp3->update([
            'google_doc_id'         => $result['id'],
            'google_doc_url'        => $result['url'] ?? null,
            'doc_generation_status' => 'completed',
            'doc_generation_error'  => null,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $sp3 = Sp3Document::find($this->sp3Id);
        if (!$sp3) return;

        $sp3->update([
            'doc_generation_status' => 'failed',
            'doc_generation_error'  => $e->getMessage(),
        ]);

        Log::error("GenerateSp3Job permanently failed for SP3 {$this->sp3Id}: " . $e->getMessage());
    }
}
