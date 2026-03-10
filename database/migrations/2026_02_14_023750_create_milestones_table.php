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
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order');
            $table->dateTime('start_date');
            $table->dateTime('deadline');
            $table->enum('status',['completed','on_progress','pending']);
            $table->boolean('is_open')->default(true);
            $table->timestamps();

            $table->unique(['academic_year_id', 'sort_order']);
            $table->index(['academic_year_id', 'status']);
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
