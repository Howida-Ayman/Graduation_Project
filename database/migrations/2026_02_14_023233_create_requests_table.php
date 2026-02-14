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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('from_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('to_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('request_type', ['team_join','supervision']);
            $table->enum('status', ['pending','accepted','rejected'])->default('pending');
            $table->timestamps();

            $table->index(['team_id']);
            $table->index(['from_user_id']);
            $table->index(['to_user_id']);
            $table->index(['request_type']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
