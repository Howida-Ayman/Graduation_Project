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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('leader_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['academic_year_id']);
            $table->index(['department_id']);
            $table->index(['leader_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
