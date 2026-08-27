<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oeuvres', function (Blueprint $table) {
            $table->id();
            // Table nommee EXPLICITEMENT : sans elle, Laravel deduit « talent » au
            // singulier depuis « talent_id » et MySQL refuse la contrainte (errno 150).
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();

            // L'un OU l'autre : une vraie photo, ou le degrade qui tient sa place
            // tant que l'hebergement des images n'est pas decide.
            $table->string('chemin')->nullable();
            $table->string('degrade')->nullable();

            $table->string('legende')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['talent_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oeuvres');
    }
};
