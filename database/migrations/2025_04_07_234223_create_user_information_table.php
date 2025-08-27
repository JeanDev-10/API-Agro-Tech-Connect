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
        Schema::create('user_informations', function (Blueprint $table) {
            $table->id(); // PK id int
            $table->text('description')->nullable()->collation('utf8mb4_unicode_ci');; // description con soporte para emojis
            $table->string('link1', 255)->nullable(); // link1 varchar
            $table->string('link2', 255)->nullable(); // link2 varchar
            $table->string('link3', 255)->nullable(); // link3 varchar
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade'); // FK user_id int con relación

            $table->timestamps(); // created_at y updated_at
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_information');
    }
};
