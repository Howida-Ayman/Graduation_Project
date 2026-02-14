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
        Schema::create('suggested_project_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('suggested_project_id')->constrained('suggested_projects')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['student_user_id','suggested_project_id'], 'uq_spf_student_project');
            $table->index(['student_user_id']);
            $table->index(['suggested_project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggested_project_favorites');
    }
};
