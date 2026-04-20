<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_milestone_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supervisor_user_id')->constrained('users')->cascadeOnDelete();

            $table->text('note');

            $table->timestamps();

            $table->unique(
                ['academic_year_id', 'milestone_id', 'supervisor_user_id'],
                'sup_milestone_note_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_milestone_notes');
    }
};