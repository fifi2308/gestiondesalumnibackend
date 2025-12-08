<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::create('follows', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id'); // la personne qui suit
        $table->unsignedBigInteger('follow_id'); // la personne suivie
        $table->timestamps();

        $table->unique(['user_id', 'follow_id']);
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('follow_id')->references('id')->on('users')->onDelete('cascade');
    });
}

public function down()
{
    Schema::dropIfExists('follows');
}

};
