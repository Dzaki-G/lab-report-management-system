<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK constraint first if it exists under Laravel's conventional naming.
        try {
            Schema::table('sample_parameters', function (Blueprint $table) {
                $table->dropForeign(['assigned_analyst_id']);
            });
        } catch (\Throwable $e) {
            // No FK existed under the conventional name — safe to ignore.
        }

        Schema::table('sample_parameters', function (Blueprint $table) {
            $table->dropColumn('assigned_analyst_id');
        });

        Schema::table('sample_parameters', function (Blueprint $table) {
            // Set automatically to whichever analyst actually submits a result —
            // attribution, not assignment. No claiming step anymore.
            $table->foreignId('filled_by_analyst_id')->nullable()
                ->after('status')
                ->constrained('users', 'user_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sample_parameters', function (Blueprint $table) {
            $table->dropForeign(['filled_by_analyst_id']);
            $table->dropColumn('filled_by_analyst_id');
        });

        Schema::table('sample_parameters', function (Blueprint $table) {
            $table->foreignId('assigned_analyst_id')->nullable()
                ->constrained('users', 'user_id')->nullOnDelete();
        });
    }
};
