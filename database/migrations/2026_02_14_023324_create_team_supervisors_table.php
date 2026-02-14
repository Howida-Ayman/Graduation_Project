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
        Schema::create('team_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('supervisor_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('supervisor_role', ['doctor','ta']);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['team_id']);
            $table->index(['supervisor_user_id']);
            $table->index(['team_id','supervisor_user_id','ended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_supervisors');
    }
};
