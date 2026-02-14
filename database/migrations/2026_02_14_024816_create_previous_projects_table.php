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
        Schema::create('previous_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('proposal_id')->constrained('proposals')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('final_score', 8, 2)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['team_id']);
            $table->index(['proposal_id']);
            $table->index(['archived_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('previous_projects');
    }
};
