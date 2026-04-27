<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->enum('type', ['text', 'file', 'image'])->default('text');
            $table->string('file_url')->nullable(); // لو في مرفق
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};