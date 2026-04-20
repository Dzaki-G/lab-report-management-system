<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sample_parameter_id');
            $table->string('result_value')->nullable();
            $table->string('result_unit')->nullable();
            $table->unsignedBigInteger('analyst_id')->nullable();
            $table->datetime('analysis_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sample_parameter_id')->references('id')->on('sample_parameters')->onDelete('cascade');
            $table->foreign('analyst_id')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
    }
};
