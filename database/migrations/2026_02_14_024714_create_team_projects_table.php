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
        Schema::create('team_projects', function (Blueprint $table) {
            
            $table->foreignId('team_id')->primary()->constrained('teams')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('proposal_id')->constrained('proposals')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('final_score', 8, 2)->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->index(['proposal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_projects');
    }
};
