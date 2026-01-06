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
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            // General contraindications
            $table->boolean('is_pregnant')->default(false);
            $table->boolean('has_epilepsy')->default(false);
            $table->boolean('has_thyroid_issues')->default(false);
            $table->boolean('has_cancer')->default(false);
            $table->boolean('has_herpes')->default(false);
            
            // Esthetic history
            $table->boolean('has_botox')->default(false);
            $table->date('botox_last_date')->nullable();
            $table->boolean('has_fillers')->default(false);
            $table->date('fillers_last_date')->nullable();
            $table->boolean('has_threads')->default(false);
            
            // Details
            $table->text('allergies')->nullable();
            $table->text('medications')->nullable();
            $table->text('additional_notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
