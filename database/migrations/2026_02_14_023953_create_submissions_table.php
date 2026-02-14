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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_requirement_id')->constrained('milestone_requirements')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignId('submitted_by_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->text('notes')->nullable();

            $table->foreignId('graded_by_user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('score', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();

            $table->timestamps();

            $table->index(['milestone_requirement_id']);
            $table->index(['team_id']);
            $table->index(['submitted_by_user_id']);
            $table->index(['graded_by_user_id']);
            $table->index(['graded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
