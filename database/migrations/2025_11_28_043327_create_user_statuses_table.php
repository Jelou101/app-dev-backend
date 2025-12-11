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
        Schema::create('user_status', function (Blueprint $table) {
            $table->bigIncrements('stat_id');
            $table->unsignedBigInteger('user_id');
            
            // Points columns - KEEP THESE
            $table->integer('total_points')->default(1000);  // Change from 0 to 1000
            
            // Add these new columns:
            $table->integer('riddle_points')->default(0);
            $table->integer('logic_points')->default(0);
            $table->integer('endurance_points')->default(0);
            
            // Other progress columns - KEEP THESE
            $table->integer('total_puzzles_solved')->default(0);
            $table->integer('best_endurance_streak')->default(0);
            $table->integer('best_endurance_score')->default(0);
            $table->integer('riddles_solved')->default(0);
            $table->integer('logic_solved')->default(0);
            $table->timestamp('last_played');
            
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
        Schema::dropIfExists('user_statuses');
    }
};
