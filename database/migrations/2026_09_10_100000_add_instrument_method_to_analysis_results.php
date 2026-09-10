<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->string('instrument')->nullable()->after('result_unit');
            $table->string('method')->nullable()->after('instrument');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->dropColumn(['instrument', 'method']);
        });
    }
};
