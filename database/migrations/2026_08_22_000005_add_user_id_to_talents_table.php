<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            // NULLABLE : les profils crees avant les comptes n'en ont pas,
            // et on ne veut pas les perdre. nullOnDelete plutot que cascade :
            // supprimer un compte ne doit pas effacer un profil deja publie.
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
