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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->timestamps();

            $table->index(['department_id']);
            $table->index(['academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
