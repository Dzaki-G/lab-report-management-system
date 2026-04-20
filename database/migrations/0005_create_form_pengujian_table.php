<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_pengujian', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->string('form_number')->unique();
            $table->string('no_spu')->nullable();
            $table->string('no_terima_sampel')->nullable();
            $table->date('received_date');
            $table->date('deadline_date')->nullable();
            $table->string('status');

            // Customer info
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();

            // User references
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('assigned_analyst_id')->nullable();

            // SPU document tracking
            $table->string('google_doc_id')->nullable();
            $table->timestamp('spu_generated_at')->nullable();
            $table->string('spu_file_path')->nullable();
            $table->string('spu_unsigned_doc_id')->nullable();
            $table->string('spu_unsigned_doc_url')->nullable();
            $table->string('spu_signed_doc_id')->nullable();
            $table->string('spu_signed_doc_url')->nullable();
            $table->timestamp('spu_signed_at')->nullable();
            $table->timestamp('spu_signed_divisi_at')->nullable();

            // LCP/LHP tracking
            $table->text('lcp_link')->nullable();
            $table->text('lhp_link')->nullable();
            $table->string('lhp_google_file_id')->nullable();
            $table->string('lhp_google_file_url')->nullable();
            $table->timestamp('lhp_uploaded_at')->nullable();
            $table->timestamp('lhp_signed_divisi_at')->nullable();
            $table->timestamp('lhp_signed_upa_at')->nullable();

            // Rejection
            $table->text('rejection_note')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('admin_id')->references('user_id')->on('users');
            $table->foreign('assigned_analyst_id')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_pengujian');
    }
};
