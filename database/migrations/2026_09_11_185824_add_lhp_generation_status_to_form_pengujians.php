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
            $table->string('lhp_generation_status')->nullable()->after('lhp_signed_upa_at')
                ->comment('queued, processing, completed, failed');
            $table->timestamp('lhp_generation_started_at')->nullable()->after('lhp_generation_status');
            $table->integer('lhp_generation_attempts')->default(0)->after('lhp_generation_started_at');
            $table->text('lhp_generation_error')->nullable()->after('lhp_generation_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_pengujian', function (Blueprint $table) {
            $table->dropColumn([
                'lhp_generation_status',
                'lhp_generation_started_at',
                'lhp_generation_attempts',
                'lhp_generation_error',
            ]);
        });
    }
};
