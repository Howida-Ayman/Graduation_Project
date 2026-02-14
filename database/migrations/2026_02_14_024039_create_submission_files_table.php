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
        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('file_url');
            $table->string('original_name')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index(['submission_id']);
            $table->index(['uploaded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_files');
    }
};
