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
        Schema::create('medical_condition_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Ciąża", "Botox", "Alergia na lidokainę"
            $table->enum('category', ['contraindication', 'esthetic']); // Red (critical) or Yellow (info)
            $table->enum('severity', ['high', 'medium', 'info']); // For badge styling
            $table->boolean('requires_date')->default(false); // If true, UI asks for date
            $table->boolean('is_active')->default(true); // Soft-hide without deleting
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_condition_types');
    }
};
