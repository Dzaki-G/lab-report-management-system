<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_analyst_id')->nullable()->after('parameter_id');
            $table->timestamp('first_viewed_at')->nullable()->after('assigned_analyst_id');
            $table->timestamp('last_viewed_at')->nullable()->after('first_viewed_at');

            $table->foreign('assigned_analyst_id')
                  ->references('user_id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sp3_documents', function (Blueprint $table) {
            $table->dropForeign(['assigned_analyst_id']);
            $table->dropColumn(['assigned_analyst_id', 'first_viewed_at', 'last_viewed_at']);
        });
    }
};
