<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter une nouvelle colonne UUID temporaire
        Schema::table('notifications', function (Blueprint $table) {
            $table->uuid('new_id')->default(\Illuminate\Support\Str::uuid())->first();
        });

        // Copier les anciennes valeurs si nécessaire (optionnel)
        // DB::table('notifications')->update(['new_id' => DB::raw('gen_random_uuid()')]);

        // Supprimer l'ancienne PK
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropPrimary('notifications_pkey'); // nom par défaut de la PK sur PostgreSQL
        });

        // Supprimer l'ancienne colonne id
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        // Renommer la nouvelle colonne en id
        Schema::table('notifications', function (Blueprint $table) {
            $table->renameColumn('new_id', 'id');
            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropPrimary('notifications_pkey');
            $table->bigIncrements('id')->first();
        });
    }
};
