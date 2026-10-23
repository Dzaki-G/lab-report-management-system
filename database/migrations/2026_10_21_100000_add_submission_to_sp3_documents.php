<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('review_status');
            $table->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');

            $table->foreign('submitted_by')
                  ->references('user_id')
                  ->on('users')
                  ->nullOnDelete();
        });

        // Backfill: SP3s on forms that already left testing were "sent" under the
        // old automatic flow — mark them submitted so Divisi can still review them.
        $sp3s = DB::table('sp3_documents')
            ->join('form_pengujian', 'form_pengujian.id', '=', 'sp3_documents.form_pengujian_id')
            ->where('form_pengujian.status', '!=', 'dalam_pengujian')
            ->select('sp3_documents.id', 'sp3_documents.parameter_id', 'sp3_documents.assigned_analyst_id', 'sp3_documents.updated_at')
            ->get();

        foreach ($sp3s as $sp3) {
            $submittedBy = $sp3->assigned_analyst_id ?? DB::table('sample_parameters')
                ->join('sp3_samples', 'sp3_samples.sample_id', '=', 'sample_parameters.sample_id')
                ->where('sp3_samples.sp3_document_id', $sp3->id)
                ->where('sample_parameters.parameter_id', $sp3->parameter_id)
                ->whereNotNull('sample_parameters.filled_by_analyst_id')
                ->value('sample_parameters.filled_by_analyst_id');

            DB::table('sp3_documents')->where('id', $sp3->id)->update([
                'submitted_at' => $sp3->updated_at ?? now(),
                'submitted_by' => $submittedBy,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn(['submitted_at', 'submitted_by']);
        });
    }
};
