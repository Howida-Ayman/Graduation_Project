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
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_course_id')
            ->constrained('project_courses')
            ->restrictOnDelete()
             ->cascadeOnUpdate();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('phase_number');
            $table->decimal('max_score', 8, 2)->default(20);
            $table->dateTime('start_date');
            $table->dateTime('deadline');
            $table->enum('status',['completed','on_progress','pending']);
            $table->boolean('is_open')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_forced_open')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_course_id', 'phase_number']);
            $table->index( 'status');
            $table->index(['start_date']);
            $table->index(['deadline']);
            $table->index(['is_open']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
