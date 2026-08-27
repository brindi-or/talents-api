<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talents', function (Blueprint $table) {
            $table->id();

            // Étape 1 du parcours d'inscription — qui êtes-vous
            $table->string('nom');
            $table->string('slug')->unique();          // sert d'identifiant dans l'URL
            $table->string('metier');
            $table->string('ville');
            $table->string('quartier')->nullable();

            // Étape 3 — vos conditions
            $table->boolean('libre')->default(true);
            $table->unsignedInteger('prix_plancher')->nullable(); // null = « sur devis »
            $table->string('whatsapp');                // JAMAIS renvoyé publiquement
            $table->string('video_url')->nullable();

            // Renseignés par l'usage, pas par le talent
            $table->decimal('note', 2, 1)->nullable();

            $table->timestamps();

            // Les deux filtres de l'accueil.
            $table->index(['metier', 'libre']);
            $table->index('ville');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talents');
    }
};
