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
        Schema::create('defense_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('defense_committees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('committee_member_id')->constrained('defense_committee_members')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('score', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();

            $table->index(['committee_id']);
            $table->index(['committee_member_id']);
            $table->index(['entered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defense_grades');
    }
};
