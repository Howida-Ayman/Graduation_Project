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
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('scope', ['admin_global','supervisor_global']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline');
            $table->boolean('is_open')->default(true);
            $table->timestamps();

            $table->index(['academic_year_id']);
            $table->index(['created_by_user_id']);
            $table->index(['scope']);
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
