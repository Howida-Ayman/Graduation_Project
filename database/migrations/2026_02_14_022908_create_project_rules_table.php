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
        Schema::create('project_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('min_team_size');
            $table->unsignedTinyInteger('max_team_size');
            $table->decimal('supervisor_max_score', 8, 2)->default(40);
            $table->decimal('defense_max_score', 8, 2)->default(40);
            $table->integer('passing_percentage')->default(50);
            $table->date('project1_team_formation_deadline')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_rules');
    }
};
