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
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->bigIncrements('session_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('game_mode', ['single_player', 'endurance']);
            $table->enum('puzzle_type', ['riddles', 'logic', 'mixed']);
            $table->integer('total_score')->default(0);
            $table->integer('puzzles_attempted')->default(0);
            $table->integer('puzzles_correct')->default(0);
            $table->integer('current_streak')->default(0);
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');

            // Foreign key constraint
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
