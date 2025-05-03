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
        Schema::create('range_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('range_id')->constrained()->onDelete('cascade')->onUpdate('cascade');;
            $table->timestamp('achieved_at')->useCurrent(); // Fecha cuando se alcanzó el rango
            $table->timestamps();

            // Evita rangos duplicados para un usuario
            $table->unique(['user_id', 'range_id']);
            // Índice para mejorar búsquedas por usuario
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_range_user');
    }
};
