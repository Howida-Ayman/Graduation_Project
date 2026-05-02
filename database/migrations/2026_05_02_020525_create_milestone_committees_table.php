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
        Schema::create('milestone_committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')
           ->constrained('teams')
           ->cascadeOnDelete()
           ->cascadeOnUpdate();
            $table->foreignId('created_by_admin_id')
           ->constrained('users')
           ->restrictOnDelete()
           ->cascadeOnUpdate();

            $table->timestamps();
 
            $table->unique(['team_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestone_committees');
    }
};
