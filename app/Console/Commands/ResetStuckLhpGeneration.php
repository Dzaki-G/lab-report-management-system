<?php

namespace App\Console\Commands;

use App\Models\FormPengujian;
use Illuminate\Console\Command;

class ResetStuckLhpGeneration extends Command
{
    protected $signature = 'app:reset-stuck-lhp';
    protected $description = 'Reset LHP generation locks stuck in "queued" for too long';

    public function handle()
    {
        $staleThresholdMinutes = 15;

        $stuck = FormPengujian::where('lhp_generation_status', 'queued')
            ->where('lhp_generation_started_at', '<', now()->subMinutes($staleThresholdMinutes))
            ->whereNull('lhp_google_file_id')
            ->get();

        foreach ($stuck as $form) {
            $form->update([
                'lhp_generation_status' => 'failed',
                'lhp_generation_error'  => 'Auto-reset: generation lock exceeded ' . $staleThresholdMinutes . ' minutes without completing.',
            ]);
            $this->line("Reset stuck LHP lock for form {$form->id} ({$form->form_number})");
        }

        $this->info("Done. Reset {$stuck->count()} stuck record(s).");
    }
}
