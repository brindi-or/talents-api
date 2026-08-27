<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un talent tel que le front le reçoit.
 *
 * ⚠️ Le champ `whatsapp` n'apparaît nulle part ici, volontairement.
 * Pour joindre un talent : GET /api/talents/{slug}/contact.
 * Si vous ajoutez un champ à cette classe, demandez-vous d'abord si un
 * inconnu peut le lire sans nuire au talent.
 */
class TalentResource extends JsonResource
{
    public function toArray(Request $requete): array
    {
        return [
            'id' => $this->slug,
            'nom' => $this->nom,
            'metier' => $this->metier,
            'ville' => $this->ville,
            'quartier' => $this->quartier,
            'libre' => $this->libre,
            'prixPlancher' => $this->prix_plancher,
            'videoUrl' => $this->video_url,
            'note' => $this->note,

            // Chargées seulement quand la relation l'a été : évite N+1 sur la liste.
            'oeuvres' => OeuvreResource::collection($this->whenLoaded('oeuvres')),
            'avis' => AvisResource::collection($this->whenLoaded('avis')),
            'nombreAvis' => $this->whenCounted('avis'),
        ];
    }
}
