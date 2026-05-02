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
        Schema::create('milestone_committee_members', function (Blueprint $table) {
    $table->id();

    $table->foreignId('committee_id')
        ->constrained('milestone_committees')
        ->cascadeOnDelete()
        ->cascadeOnUpdate();

    $table->foreignId('member_user_id')
        ->constrained('users')
        ->restrictOnDelete()
        ->cascadeOnUpdate();

    $table->enum('member_role', ['doctor', 'ta'])->nullable();

    $table->timestamps();

    $table->unique(['committee_id', 'member_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestone_committee_members');
    }
};
