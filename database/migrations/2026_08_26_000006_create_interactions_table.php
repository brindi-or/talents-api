<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qui se passe sur le profil d'un talent : vues et mises en relation.
 *
 * Une ligne par événement plutôt que deux compteurs sur `talents` : le talent
 * veut voir l'activité de sa semaine, pas seulement un total depuis toujours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();

            // 'vue' = quelqu'un a ouvert la fiche
            // 'contact' = quelqu'un a cliqué pour écrire sur WhatsApp
            $table->string('type', 16);

            // Empreinte du visiteur (IP + navigateur, hachée) : sert UNIQUEMENT
            // à ne pas compter dix fois la même personne qui rafraîchit.
            // On ne stocke pas l'IP en clair — on n'en a pas besoin.
            $table->string('empreinte', 64)->nullable();

            $table->timestamps();

            // La requête du tableau de bord : « mes interactions, par type,
            // depuis telle date ».
            $table->index(['talent_id', 'type', 'created_at']);

            // La déduplication cherche exactement cette combinaison.
            $table->index(['talent_id', 'empreinte', 'type', 'created_at'], 'interactions_dedup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
