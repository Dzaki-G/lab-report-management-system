<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_pengujian_id');
            $table->string('sample_code');
            $table->string('sample_name');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('form_pengujian_id')->references('id')->on('form_pengujian')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
