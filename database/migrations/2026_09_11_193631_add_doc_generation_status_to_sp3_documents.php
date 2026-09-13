<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->string('doc_generation_status')->nullable()->after('status')
                ->comment('queued, processing, completed, failed');
            $table->text('doc_generation_error')->nullable()->after('doc_generation_status');
        });
    }

    public function down(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->dropColumn(['doc_generation_status', 'doc_generation_error']);
        });
    }
};
