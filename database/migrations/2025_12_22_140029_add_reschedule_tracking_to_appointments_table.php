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
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedInteger('rescheduled_count')->default(0)->after('reminder_sent_at');
            $table->timestamp('first_rescheduled_at')->nullable()->after('rescheduled_count');
            $table->timestamp('last_rescheduled_at')->nullable()->after('first_rescheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['rescheduled_count', 'first_rescheduled_at', 'last_rescheduled_at']);
        });
    }
};
