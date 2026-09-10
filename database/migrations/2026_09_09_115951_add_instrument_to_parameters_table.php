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
        if (!Schema::hasColumn('parameters', 'instrument')) {
            Schema::table('parameters', function (Blueprint $table) {
                $table->string('instrument')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('parameters', function (Blueprint $table) {
            $table->dropColumn('instrument');
        });
    }
};
