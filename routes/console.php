<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\FormPengujian;
use App\Services\NotificationService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Detect LHP jobs stuck in queued/processing for >10 minutes and mark them failed
Schedule::call(function () {
    $stuck = FormPengujian::whereIn('lhp_generation_status', ['queued', 'processing'])
        ->where('lhp_generation_started_at', '<', now()->subMinutes(10))
        ->whereNull('lhp_google_file_id')
        ->get();

    $notificationService = new NotificationService();

    foreach ($stuck as $form) {
        $form->update([
            'lhp_generation_status' => 'failed',
            'lhp_generation_error' => 'Proses generate melebihi batas waktu (timeout).',
        ]);

        $notificationService->create(
            $form->admin_id,
            'lhp_failed',
            'Generate LHP Timeout',
            "LHP untuk form {$form->form_number} tidak selesai dalam 10 menit dan ditandai gagal.",
            ['form_id' => $form->id]
        );
    }
})->everyTenMinutes()->name('detect-stuck-lhp');
