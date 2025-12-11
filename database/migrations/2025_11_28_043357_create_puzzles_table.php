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
        Schema::create('puzzles', function (Blueprint $table) {
            $table->bigIncrements('puzzle_id');
            $table->enum('puzzle_type', ['riddles', 'logic']);
            $table->text('question_text');
            $table->string('correct_answer', 500);
            $table->text('hint_text')->nullable();
            $table->text('explanation_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzles');
    }
};
