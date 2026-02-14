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
        Schema::create('previous_project_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('previous_project_id')->constrained('previous_projects')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['student_user_id','previous_project_id'], 'uq_ppf_student_prev');
            $table->index(['student_user_id']);
            $table->index(['previous_project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('previous_project_favorites');
    }
};
