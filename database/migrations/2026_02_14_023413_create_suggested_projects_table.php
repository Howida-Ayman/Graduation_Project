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
        Schema::create('suggested_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('recommended_tools')->nullable();
            $table->foreignId('created_by_admin_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['department_id']);
            $table->index(['created_by_admin_id']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggested_projects');
    }
};
