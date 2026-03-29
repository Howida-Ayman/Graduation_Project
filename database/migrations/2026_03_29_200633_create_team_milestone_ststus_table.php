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
        Schema::create('team_milestone_ststus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')
                ->constrained('teams')
                ->cascadeOnDelete();

            $table->foreignId('milestone_id')
                ->constrained('milestones')
                ->cascadeOnDelete();

            $table->enum('status', ['pending_submission', 'on_track', 'delayed'])
                ->default('pending_submission');

            $table->timestamps();

            $table->unique(['team_id', 'milestone_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_milestone_ststus');
    }
};
