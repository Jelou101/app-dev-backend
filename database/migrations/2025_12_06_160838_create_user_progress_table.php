<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProgressTable extends Migration
{
    public function up()
    {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('game_mode'); // 'single_journey', 'endurance', etc.
            $table->integer('current_score')->default(0);
            $table->integer('hint_count')->default(0);
            $table->json('current_riddle')->nullable(); // Store current riddle data
            $table->json('attempted_riddles')->nullable(); // Track completed riddles
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();
            
            // Reference user_id column in users table
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'game_mode']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_progress');
    }
}