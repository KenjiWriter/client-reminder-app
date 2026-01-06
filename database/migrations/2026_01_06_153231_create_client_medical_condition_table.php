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
        Schema::create('client_medical_condition', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('medical_condition_type_id');
            $table->date('occurred_at')->nullable(); // For "Last Botox Date", etc.
            $table->text('notes')->nullable(); // Additional information
            $table->boolean('is_active')->default(true); // Does client currently have this condition?
            $table->timestamps();

            // Foreign keys
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('medical_condition_type_id')->references('id')->on('medical_condition_types')->onDelete('cascade');

            // Ensure a client can't have the same condition type twice
            $table->unique(['client_id', 'medical_condition_type_id'], 'client_condition_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_medical_condition');
    }
};
