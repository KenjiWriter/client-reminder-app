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
            $table->boolean('is_paid')->default(false)->after('status');
            $table->enum('payment_method', ['cash', 'card', 'transfer'])->nullable()->after('is_paid');
            $table->dateTime('payment_date')->nullable()->after('payment_method');
            $table->decimal('price', 8, 2)->nullable()->after('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'payment_method', 'payment_date', 'price']);
        });
    }
};
