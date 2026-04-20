<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sp3_samples', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sp3_document_id');
            $table->unsignedBigInteger('sample_id');
            $table->timestamp('paraf_date')->nullable();
            $table->timestamps();

            $table->unique(['sp3_document_id', 'sample_id']);
            $table->foreign('sp3_document_id')->references('id')->on('sp3_documents')->onDelete('cascade');
            $table->foreign('sample_id')->references('id')->on('samples')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sp3_samples');
    }
};
