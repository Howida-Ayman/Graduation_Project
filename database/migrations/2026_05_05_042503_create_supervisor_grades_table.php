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
        Schema::create('supervisor_grades', function (Blueprint $table) {
    $table->id();

    $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
    $table->foreignId('project_course_id')->constrained('project_courses')->restrictOnDelete();

    $table->decimal('grade', 8, 2);

    $table->foreignId('graded_by_user_id')
        ->constrained('users')
        ->restrictOnDelete();

    $table->timestamp('graded_at')->nullable();
    $table->text('notes')->nullable();

    $table->timestamps();

    $table->unique(['team_id', 'project_course_id'], 'supervisor_grade_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisor_grades');
    }
};
