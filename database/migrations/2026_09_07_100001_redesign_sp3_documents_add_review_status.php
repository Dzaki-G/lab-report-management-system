<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK constraint first if it exists under Laravel's conventional naming.
        try {
            Schema::table('sp3_documents', function (Blueprint $table) {
                $table->dropForeign(['assigned_analyst_id']);
            });
        } catch (\Throwable $e) {
            // No FK existed under the conventional name — safe to ignore.
        }

        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->dropColumn([
                'google_doc_id',       // SP3 is never rendered to a Google Doc anymore
                'google_doc_url',
                'assigned_analyst_id', // no more single assignment — collective/attributed input instead
                'signed_at',           // SP3s are never individually signed in the new flow
                'needs_revision',      // replaced by review_status
            ]);
        });

        // Repurpose the existing revision_note field instead of adding a redundant new one.
        // Requires doctrine/dbal if on Laravel < 11 — see note in chat if this errors.
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->renameColumn('revision_note', 'rejection_note');
        });

        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->string('review_status', 20)->default('pending')->after('status');
            // Values used in app logic: pending | rejected | resubmitted | approved
            $table->timestamp('rejected_at')->nullable()->after('rejection_note');
            $table->foreignId('rejected_by')->nullable()
                ->after('rejected_at')
                ->constrained('users', 'user_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['review_status', 'rejected_at', 'rejected_by']);
        });

        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->renameColumn('rejection_note', 'revision_note');
        });

        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->string('google_doc_id')->nullable();
            $table->string('google_doc_url')->nullable();
            $table->foreignId('assigned_analyst_id')->nullable()
                ->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->boolean('needs_revision')->default(false);
        });
    }
};
