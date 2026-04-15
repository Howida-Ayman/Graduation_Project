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
        Schema::create('submission_ai_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('external_sources_percent', 5, 2)->nullable();
            $table->longText('sources_payload')->nullable();
            $table->string('model_version')->nullable();
            $table->timestamps();

            $table->index(['submission_id']);
            $table->index(['model_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_ai_checks');
    }
};
