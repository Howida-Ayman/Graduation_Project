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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignId('submitted_by_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete()->cascadeOnUpdate();

            $table->foreignId('project_type_id')->constrained('project_types')->restrictOnDelete()->cascadeOnUpdate();

            $table->foreignId('suggested_project_id')->nullable()
                ->constrained('suggested_projects')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('title');
            $table->text('description');
            $table->text('problem_statement')->nullable();
            $table->text('technologies')->nullable();
            $table->string('attachment_file')->nullable();
            $table->string('image_url')->nullable();

            $table->enum('status', ['pending','approved','rejected'])->default('pending');

            $table->foreignId('decided_by_admin_id')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('decided_at')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            $table->index(['team_id']);
            $table->index(['department_id']);
            $table->index(['project_type_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
