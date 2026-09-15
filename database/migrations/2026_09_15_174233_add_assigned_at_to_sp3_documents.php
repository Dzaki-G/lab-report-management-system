<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->after('assigned_analyst_id');
        });
    }
};
