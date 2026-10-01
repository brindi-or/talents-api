<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue une photo d'une vidéo.
 *
 * Sans cette colonne, le front devrait deviner d'après l'extension du
 * fichier — et se tromperait dès qu'un nom sortirait de l'ordinaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oeuvres', function (Blueprint $table) {
            // 'image' | 'video' — 'image' par défaut : les lignes existantes
            // viennent du seeder et ne sont pas des vidéos.
            $table->string('type', 8)->default('image')->after('talent_id');

            // Taille en octets : sert à afficher « 4,2 Mo » et à surveiller
            // ce que le disque encaisse.
            $table->unsignedInteger('poids')->nullable()->after('chemin');
        });
    }

    public function down(): void
    {
        Schema::table('oeuvres', function (Blueprint $table) {
            $table->dropColumn(['type', 'poids']);
        });
    }
};
