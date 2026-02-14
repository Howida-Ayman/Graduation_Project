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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->longText('payload')->nullable();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action')->nullable();
            $table->foreignId('created_by_user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['type']);
            $table->index(['entity_type','entity_id']);
            $table->index(['created_by_user_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
