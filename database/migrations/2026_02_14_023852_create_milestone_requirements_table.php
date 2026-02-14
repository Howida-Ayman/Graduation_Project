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
        Schema::create('milestone_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['milestone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestone_requirements');
    }
};
