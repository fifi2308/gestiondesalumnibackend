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
       Schema::create('notifications', function (Blueprint $table) {
   $table->uuid('id')->primary();
    $table->string('type'); // classe de la notification Laravel
    $table->morphs('notifiable'); // ajoute notifiable_id + notifiable_type
    $table->json('data');       // contenu de la notification
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
