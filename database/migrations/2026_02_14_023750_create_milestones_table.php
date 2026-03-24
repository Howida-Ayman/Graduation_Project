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
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('phase_number');
            $table->dateTime('start_date');
            $table->dateTime('deadline');
            $table->enum('status',['completed','on_progress','pending']);
            $table->boolean('is_open')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('phase_number');
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
