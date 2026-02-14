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
        Schema::create('team_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('student_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('role_in_team')->nullable(); // leader/member...
            $table->enum('status', ['active','left'])->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id','student_user_id']);
            $table->unique(['student_user_id','academic_year_id']); // one team per year
            $table->index(['team_id']);
            $table->index(['student_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_memberships');
    }
};
