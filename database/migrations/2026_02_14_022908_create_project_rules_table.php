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
        Schema::create('project_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('min_team_size');
            $table->unsignedInteger('max_team_size');
            $table->text('rules')->nullable();
            $table->foreignId('created_by_admin_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('academic_year_id');
            $table->index(['academic_year_id','is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_rules');
    }
};
