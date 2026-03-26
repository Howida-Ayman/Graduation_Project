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
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete()->cascadeOnUpdate(); // to join team
            $table->foreignId('from_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();  // who sent the request
            $table->foreignId('to_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();  // who receives the request (team leader or supervisor)
            $table->enum('request_type', ['team_join','team_form','team_invite','supervision']); 
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
