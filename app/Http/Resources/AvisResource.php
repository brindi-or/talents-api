<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvisResource extends JsonResource
{
    public function toArray(Request $requete): array
    {
        return [
            'auteur' => $this->auteur,
            'texte' => $this->texte,
            'etoiles' => $this->etoiles,
            // Le front affiche « juillet », pas une date complete.
            'mois' => $this->created_at?->locale('fr')->translatedFormat('F'),
        ];
    }
}
