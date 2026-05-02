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
Schema::create('milestone_committee_grades', function (Blueprint $table) {
    $table->id();

    $table->foreignId('milestone_id')
        ->constrained('milestones')
        ->cascadeOnDelete();

    $table->foreignId('team_id')
        ->constrained('teams')
        ->cascadeOnDelete();

    $table->foreignId('committee_id')
        ->constrained('milestone_committees')
        ->cascadeOnDelete();

    $table->foreignId('project_course_id')
        ->constrained('project_courses')
        ->cascadeOnUpdate();

    $table->decimal('grade', 8, 2);
    $table->foreignId('graded_by_user_id')
    ->constrained('users')
    ->restrictOnDelete();

    $table->timestamp('graded_at')->nullable();

    $table->text('notes')->nullable();

    $table->timestamps();

   $table->unique(
    ['milestone_id', 'team_id', 'project_course_id'],
    'milestone_team_course_unique'
);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestone_committee_grades');
    }
};
