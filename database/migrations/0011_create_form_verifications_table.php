<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_pengujian_id');
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('form_pengujian_id')->references('id')->on('form_pengujian')->onDelete('cascade');
            $table->foreign('verified_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_verifications');
    }
};
