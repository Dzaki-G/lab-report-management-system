<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK constraints first if they exist under Laravel's conventional naming.
        // Wrapped individually so a missing/differently-named constraint doesn't block the rest.
        foreach (['assigned_analyst_id', 'rejected_by'] as $col) {
            try {
                Schema::table('form_pengujian', function (Blueprint $table) use ($col) {
                    $table->dropForeign([$col]);
                });
            } catch (\Throwable $e) {
                // No FK existed under the conventional name — safe to ignore.
            }
        }

        Schema::table('form_pengujian', function (Blueprint $table) {
            $table->dropColumn([
                // Legacy/duplicate fields, no longer referenced by the new workflow
                'google_doc_id',
                'spu_file_path',
                'assigned_analyst_id',   // dead: assignment never happened at form level in practice

                // SPU generation/signing — dropped entirely, no more SPU
                'spu_generated_at',
                'spu_unsigned_doc_id',
                'spu_unsigned_doc_url',
                'spu_signed_doc_id',
                'spu_signed_doc_url',
                'spu_signed_at',
                'spu_signed_divisi_at',
                'no_spu',                // confirmed unused going forward

                // Redundant LCP/LHP fields — consolidating to lhp_google_file_id only
                'lcp_link',
                'lhp_link',
                'lhp_google_file_url',

                // Form-level rejection — rejection now lives on sp3_documents instead
                'rejection_note',
                'rejected_by',
                'rejected_at',
            ]);
        });

        // Widen status column to comfortably fit the new (shorter) status set.
        // Requires doctrine/dbal if on Laravel < 11 — see note in chat if this errors.
        Schema::table('form_pengujian', function (Blueprint $table) {
            $table->string('status', 50)->default('dalam_pengujian')->change();
        });
    }

    public function down(): void
    {
        // Best-effort structural rollback. Data that existed in these columns
        // before the up() migration ran is NOT restored — only the columns are.
        Schema::table('form_pengujian', function (Blueprint $table) {
            $table->string('google_doc_id')->nullable();
            $table->string('spu_file_path')->nullable();
            $table->foreignId('assigned_analyst_id')->nullable()
                ->constrained('users', 'user_id')->nullOnDelete();

            $table->timestamp('spu_generated_at')->nullable();
            $table->string('spu_unsigned_doc_id')->nullable();
            $table->string('spu_unsigned_doc_url')->nullable();
            $table->string('spu_signed_doc_id')->nullable();
            $table->string('spu_signed_doc_url')->nullable();
            $table->timestamp('spu_signed_at')->nullable();
            $table->timestamp('spu_signed_divisi_at')->nullable();
            $table->string('no_spu')->nullable();

            $table->string('lcp_link')->nullable();
            $table->string('lhp_link')->nullable();
            $table->string('lhp_google_file_url')->nullable();

            $table->text('rejection_note')->nullable();
            $table->foreignId('rejected_by')->nullable()
                ->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
        });
    }
};
