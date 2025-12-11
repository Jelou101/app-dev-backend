<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('riddles', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->text('hint')->nullable();
            $table->string('answer');
            $table->text('explanation')->nullable();
            $table->string('source')->default('manual');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('riddles');
    }
};