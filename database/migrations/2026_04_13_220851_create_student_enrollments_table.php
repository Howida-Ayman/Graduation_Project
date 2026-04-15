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
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')
        ->constrained('users')
        ->restrictOnDelete()
        ->cascadeOnUpdate();

    $table->foreignId('academic_year_id')
        ->constrained('academic_years')
        ->restrictOnDelete()
        ->cascadeOnUpdate();

    $table->enum('status', ['active', 'failed', 'graduated'])
        ->default('active');

    $table->timestamps();

    // الطالب يظهر مرة واحدة فقط في كل سنة
    $table->unique(['student_user_id', 'academic_year_id']);

    $table->index(['student_user_id']);
    $table->index(['academic_year_id']);
    $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
