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
        Schema::create('defense_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')
            ->constrained('defense_committees')
            ->cascadeOnDelete()
            ->cascadeOnUpdate();

           $table->foreignId('entered_by_user_id')
           ->constrained('users')
           ->restrictOnDelete()
           ->cascadeOnUpdate();

           $table->decimal('grade', 8, 2);
           $table->text('notes')->nullable();
           $table->timestamp('entered_at')->nullable();

           $table->timestamps();

            $table->unique('committee_id'); // درجة واحدة فقط لكل مناقشة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defense_grades');
    }
};
