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
            $table->string('status')->default('confirmed')->after('reminder_sent_at');
            
            // Client requests
            $table->timestamp('requested_starts_at')->nullable()->after('status');
            $table->timestamp('requested_at')->nullable()->after('requested_starts_at');
            
            // Mother's suggestions
            $table->timestamp('suggested_starts_at')->nullable()->after('requested_at');
            $table->integer('suggested_duration_minutes')->nullable()->after('suggested_starts_at');
            $table->text('suggested_note')->nullable()->after('suggested_duration_minutes');
            $table->timestamp('suggestion_created_at')->nullable()->after('suggested_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'requested_starts_at',
                'requested_at',
                'suggested_starts_at',
                'suggested_duration_minutes',
                'suggested_note',
                'suggestion_created_at',
            ]);
        });
    }
};
