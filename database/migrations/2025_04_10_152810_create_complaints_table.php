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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->morphs('complaintable'); // Crea complaintable_id y complaintable_type
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->onDelete('cascade');
            $table->timestamps();

            // Índice para mejorar búsquedas polimórficas
            $table->index(['complaintable_id', 'complaintable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
