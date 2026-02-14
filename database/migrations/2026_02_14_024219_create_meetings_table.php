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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete()->cascadeOnUpdate();
            $table->dateTime('scheduled_at');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['team_id']);
            $table->index(['scheduled_at']);
            $table->index(['created_by_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
