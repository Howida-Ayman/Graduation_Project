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
            $table->unsignedTinyInteger('seat_order')->nullable();
            $table->timestamps();

            
            $table->unique(['committee_id', 'member_user_id']);
            $table->unique( ['committee_id', 'member_role', 'seat_order'],'committee_role_seat_unique');
            $table->index(['committee_id']);
            $table->index(['member_user_id']);
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
