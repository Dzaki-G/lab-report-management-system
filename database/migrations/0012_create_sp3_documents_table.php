<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sp3_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_pengujian_id');
            $table->unsignedBigInteger('parameter_id')->nullable();
            $table->string('sp3_number');
            $table->string('google_doc_id')->nullable();
            $table->string('google_doc_url')->nullable();
            $table->string('no_sppp')->nullable();
            $table->string('ik')->nullable();
            $table->unsignedBigInteger('assigned_analyst_id')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('signed_at')->nullable();

            // LCP fields
            $table->string('lcp_google_file_id')->nullable();
            $table->string('lcp_google_file_url')->nullable();
            $table->timestamp('lcp_uploaded_at')->nullable();

            // Revision tracking
            $table->boolean('needs_revision')->default(false);
            $table->text('revision_note')->nullable();

            $table->timestamps();

            $table->foreign('form_pengujian_id')->references('id')->on('form_pengujian')->onDelete('cascade');
            $table->foreign('parameter_id')->references('id')->on('parameters')->onDelete('set null');
            $table->foreign('assigned_analyst_id')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sp3_documents');
    }
};
