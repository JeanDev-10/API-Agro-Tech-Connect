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
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['positivo', 'negativo']);
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
            // para crear los dos campos para poliformicos
            $table->morphs('reactionable');
            // Evitar reacciones duplicadas del mismo usuario al mismo elemento
            $table->unique(['user_id', 'reactionable_id', 'reactionable_type']);

            // Índice para mejorar búsquedas polimórficas
            $table->index(['reactionable_id', 'reactionable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
