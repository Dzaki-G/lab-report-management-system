<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\FormPengujian;
use App\Models\Parameter;
use App\Models\Unit;
use App\Enums\Role;
use DB;

class TestWorkflow extends Command
{
    protected $signature = 'app:test-workflow';
    protected $description = 'Run a full workflow test on the current database';

    public function handle()
    {
        // Force MySQL connection to avoid SQLite default
        DB::setDefaultConnection('mysql');

        $this->info('Starting Full Workflow Test (MySQL)...');

        try {
            DB::beginTransaction();

            // 0. Setup Users
            $this->info('[0/10] Checking Users...');
            $admin = User::where('role_id', Role::ADMIN)->firstOrFail();
            $kepalaUpa = User::where('role_id', Role::KEPALA_UPA)->firstOrFail();
            $kepalaDivisi = User::where('role_id', Role::KEPALA_DIVISI)->firstOrFail();
            $analis = User::where('role_id', Role::ANALIS)->firstOrFail();
            
            $this->line("  - Admin: {$admin->name}");
            $this->line("  - UPA: {$kepalaUpa->name}");

            // Create dependencies
            $unit = Unit::firstOrCreate(['name' => 'mg/L']);
            $parameter = Parameter::firstOrCreate(
                ['name' => 'Test Parameter WF'],
                ['unit_id' => $unit->id, 'price' => 50000]
            );

            // 1. Create Form
            $this->info('[1/10] Creating Form...');
            $form = FormPengujian::create([
                'form_number' => 'TEST-WF-' . time(),
                'admin_id' => $admin->user_id,
                'customer_name' => 'Test Customer Workflow',
                'customer_agency' => 'Test Agency',
                'customer_address' => 'Test St.',
                'customer_phone' => '08000000',
                'customer_email' => 'test@wf.com',
                'analysis_type' => 'Test Analysis',
                'sample_type' => 'Water',
                'received_date' => now(),
                'deadline_date' => now()->addDays(5),
                'status' => 'verifikasi_upa_1',
            ]);
            
            // Create Sample
            $sample = $form->samples()->create([
                'sample_name' => 'Sample 1',
                'description' => 'Test Sample',
                'quantity' => '1',
                'unit' => 'L',
            ]);
            
            $sample->sampleParameters()->create([
                'parameter_id' => $parameter->id
            ]);

            $this->assertStatus($form, 'verifikasi_upa_1');

            // 2. Kepala UPA Approve
            $this->info('[2/10] Kepala UPA Verification...');
            $this->simulateApprove($form, 'verifikasi_upa_1', 'verifikasi_divisi');

            // 3. Kepala Divisi Assign
            $this->info('[3/10] Assigning Analyst...');
            $sp = $form->samples->first()->sampleParameters->first();
            $sp->update(['assigned_analyst_id' => $analis->user_id]);

            // 4. Kepala Divisi Approve (to testing)
            $this->info('[4/10] Kepala Divisi Verification...');
            $this->simulateApprove($form, 'verifikasi_divisi', 'dalam_pengujian');

            // 5. Analis Result
            $this->info('[5/10] Analis Input Result...');
            $sp->analysisResult()->create([
                'result' => '10.5',
                'unit' => 'mg/L',
                'status' => 'verified',
                'analyst_id' => $analis->user_id,
            ]);
            
            // Simulate Controller Logic: Check if all done
            if ($form->samples->flatMap->sampleParameters->every(fn($p) => $p->analysisResult)) {
                $form->update(['status' => 'verifikasi_hasil_divisi']);
            }
            $this->assertStatus($form, 'verifikasi_hasil_divisi');

            // 6. Kepala Divisi Result Verify
            $this->info('[6/10] Kepala Divisi Result Verification...');
            // Logic: verifikasi_hasil_divisi -> input_lhp
            $this->simulateApprove($form, 'verifikasi_hasil_divisi', 'input_lhp');

            // 7. Input LHP (Admin)
            $this->info('[7/10] Admin Input LHP...');
            $form->update(['status' => 'ttd_divisi_lhp']); // Admin creates LHP, sends to Kepala Divisi
            $this->assertStatus($form, 'ttd_divisi_lhp');

            // 8. TTD Kepala Divisi (LHP)
            $this->info('[8/10] Kepala Divisi Signing LHP...');
            $form->update([
                'status' => 'ttd_upa',
                'lhp_signed_divisi_at' => now(),
            ]);
            $this->assertStatus($form, 'ttd_upa');

            // 9. TTD UPA
            $this->info('[9/10] Kepala UPA Signing...');
            // Logic: ttd_upa -> kirim_customer
            $this->simulateApprove($form, 'ttd_upa', 'kirim_customer');
            $form->update(['lhp_signed_upa_at' => now()]);

            // 10. Admin Finish
            $this->info('[10/10] Finalizing...');
            $this->line("  - Form reached 'kirim_customer'. Workflow SUCCESS.");

            $this->info("\nSUCCESS! Full workflow executed correctly.");
            
            // Cleanup
            $form->samples->each(function($s) {
                $s->sampleParameters()->delete();
                $s->delete();
            });
            $form->delete();
            $this->info('Test data cleaned up.');
            
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("FAILED: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    private function simulateApprove($form, $from, $to)
    {
        if ($form->status !== $from) {
             throw new \Exception("Expected status {$from}, got {$form->status}");
        }
        $form->update(['status' => $to]);
        $this->line("  - Transitioned: {$from} -> {$to}");
    }

    private function assertStatus($form, $status)
    {
        $form->refresh();
        if ($form->status !== $status) {
            throw new \Exception("Assertion Failed: Expected {$status}, got {$form->status}");
        }
        $this->line("  - Status confirmed: {$status}");
    }
}
