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
        Schema::create('logic_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->text('options');
            $table->string('answer');
            $table->text('hint')->nullable();
            $table->text('explanation')->nullable();
            $table->string('source')->default('openrouter_ai');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logic_questions');
    }
};