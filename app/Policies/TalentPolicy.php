<?php

namespace App\Policies;

use App\Models\Talent;
use App\Models\User;

/**
 * Qui a le droit de toucher à un profil.
 *
 * Règle unique : un talent ne modifie que le sien.
 * Consulter reste ouvert à tous — c'est un annuaire public, pas un intranet.
 */
class TalentPolicy
{
    public function update(User $utilisateur, Talent $talent): bool
    {
        // Un profil créé avant les comptes n'a pas de user_id : personne ne
        // peut le modifier tant qu'il n'a pas été rattaché. Le comparer à
        // null laisserait n'importe qui l'éditer.
        return $talent->user_id !== null
            && $talent->user_id === $utilisateur->id;
    }

    public function delete(User $utilisateur, Talent $talent): bool
    {
        return $this->update($utilisateur, $talent);
    }
}
