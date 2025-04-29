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
        Schema::create('replay_comments', function (Blueprint $table) {
            $table->id();
            $table->text('comment')->collation('utf8mb4_unicode_ci');
            $table->foreignId('comment_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replay_comments');
    }
};
