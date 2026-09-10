<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrective migration: an earlier migration
 * (2026_09_07_100001_redesign_sp3_documents_add_review_status)
 * incorrectly dropped google_doc_id/google_doc_url on the assumption that
 * SP3 would no longer be individually generated as a Google Doc.
 *
 * Confirmed: SP3 IS still generated as a real Google Doc (like before),
 * separate from the LHP. This migration restores those two columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->string('google_doc_id')->nullable()->after('ik');
            $table->string('google_doc_url')->nullable()->after('google_doc_id');
        });
    }

    public function down(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->dropColumn(['google_doc_id', 'google_doc_url']);
        });
    }
};
