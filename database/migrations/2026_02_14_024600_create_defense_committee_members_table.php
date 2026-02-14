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
        Schema::create('defense_committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('defense_committees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('member_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('member_role', ['doctor','ta']);
            $table->boolean('is_chair')->default(false);
            $table->timestamps();

            $table->unique(['committee_id','member_user_id']);
            $table->index(['committee_id']);
            $table->index(['member_user_id']);
            $table->index(['is_chair']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defense_committee_members');
    }
};
