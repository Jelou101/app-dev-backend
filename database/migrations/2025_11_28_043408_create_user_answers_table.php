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
        Schema::create('user_answers', function (Blueprint $table) {
            $table->bigIncrements('answer_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('puzzle_id');
            $table->string('user_answer', 500)->nullable();
            $table->boolean('is_correct');
            $table->integer('points_earned')->default(0);
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('session_id')
                  ->references('session_id')
                  ->on('game_sessions')
                  ->onDelete('cascade');

            $table->foreign('puzzle_id')
                  ->references('puzzle_id')
                  ->on('puzzles')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_answers');
    }
};
