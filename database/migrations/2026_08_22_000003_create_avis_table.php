<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            // Table nommee EXPLICITEMENT : sans elle, Laravel deduit « talent » au
            // singulier depuis « talent_id » et MySQL refuse la contrainte (errno 150).
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->string('auteur');
            $table->text('texte');
            $table->unsignedTinyInteger('etoiles')->nullable();
            $table->timestamps();

            $table->index('talent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
