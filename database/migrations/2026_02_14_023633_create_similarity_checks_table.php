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
        Schema::create('similarity_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('score_percent', 5, 2);
            $table->longText('sources_payload')->nullable();
            $table->string('model_version')->nullable();
            $table->timestamps();

            $table->index(['proposal_id']);
            $table->index(['model_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('similarity_checks');
    }
};
