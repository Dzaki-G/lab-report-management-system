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
        Schema::table('form_pengujian', function (Blueprint $table) {
            $table->string('customer_institution')->nullable()->after('customer_phone');
            $table->string('customer_position')->nullable()->after('customer_institution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_pengujian', function (Blueprint $table) {
            $table->dropColumn(['customer_institution', 'customer_position']);
        });
    }
};
