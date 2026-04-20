<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearAllForms extends Command
{
    protected $signature = 'app:clear-forms {--force : Skip confirmation}';
    protected $description = 'Clear all form pengujian data (forms, samples, parameters, verifications, notifications, SP3 documents)';

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('⚠️  Ini akan menghapus SEMUA data form, sampel, parameter, verifikasi, notifikasi, dan dokumen SP3. Lanjutkan?')) {
            $this->info('Dibatalkan.');
            return;
        }

        $this->info('Menghapus semua data form...');

        DB::statement('PRAGMA foreign_keys = OFF;');

        $tables = [
            'analysis_results',
            'sample_parameters',
            'samples',
            'form_verifications',
            'sp3_documents',
            'notifications',
            'form_pengujian',
        ];

        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                DB::table($table)->delete();
                $this->line("  ✓ {$table}: {$count} records deleted");
            } catch (\Exception $e) {
                $this->warn("  ⚠ {$table}: " . $e->getMessage());
            }
        }

        DB::statement('PRAGMA foreign_keys = ON;');

        $this->newLine();
        $this->info('✅ Semua data form berhasil dihapus!');
    }
}
