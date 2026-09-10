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
            $table->string('lhp_number')->nullable()->after('form_number');
            $table->string('customer_address')->nullable()->after('customer_phone');
            $table->string('sample_type')->nullable()->after('customer_address');
            $table->string('sample_matrix')->nullable()->after('sample_type');
            $table->string('sample_name_label')->nullable()->after('sample_matrix');
            $table->string('sample_form')->nullable()->after('sample_name_label');
            $table->string('sample_packing')->nullable()->after('sample_form');
            $table->unsignedTinyInteger('sample_count')->nullable()->after('sample_packing');
        });
    }

    public function down(): void
    {
        Schema::table('form_pengujian', function (Blueprint $table) {
            $table->dropColumn([
                'lhp_number', 'customer_address', 'sample_type', 'sample_matrix',
                'sample_name_label', 'sample_form', 'sample_packing', 'sample_count',
            ]);
        });
    }
};
