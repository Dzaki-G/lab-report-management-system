<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            // GDrive link to the test pictures for this SP3's parameter — sent to the
            // customer by Admin together with the LHP PDF (pictures no longer in the LHP).
            $table->string('photo_link', 500)->nullable()->after('lcp_uploaded_at');
            $table->timestamp('photo_link_updated_at')->nullable()->after('photo_link');
        });
    }

    public function down(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->dropColumn(['photo_link', 'photo_link_updated_at']);
        });
    }
};
