<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adapte la table users livree par Laravel au contexte d'ici :
 * on se connecte avec son TELEPHONE, pas avec un email.
 * Les talents ont tous un numero WhatsApp ; beaucoup n'ont pas d'adresse mail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telephone')->unique()->after('name');

            // L'email devient facultatif : il n'est plus l'identifiant.
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telephone']);
            $table->dropColumn('telephone');
            $table->string('email')->nullable(false)->change();
        });
    }
};
