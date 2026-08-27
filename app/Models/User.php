<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Le compte d'un talent.
 *
 * ⚠️ Remplace le User livré par Laravel. Deux différences :
 *  - on se connecte avec le TÉLÉPHONE, pas l'email (les talents ont un
 *    numéro WhatsApp, rarement une adresse mail) ;
 *  - un compte porte au plus un profil de talent.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** Explicite pour la même raison que Talent : la déduction donnait un singulier. */
    protected $table = 'users';

    protected $fillable = [
        'name',
        'telephone',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function talent(): HasOne
    {
        return $this->hasOne(Talent::class);
    }

    /**
     * Normalise un numéro pour la comparaison : « +237 600 00 00 01 »,
     * « 237600000001 » et « +237600000001 » doivent désigner le même compte.
     * Sans cela, un talent se recrée un compte à chaque faute de frappe.
     */
    public static function normaliserTelephone(string $numero): string
    {
        return preg_replace('/\D/', '', $numero);
    }
}
