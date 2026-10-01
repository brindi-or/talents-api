<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OeuvreResource extends JsonResource
{
    public function toArray(Request $requete): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'imageUrl' => $this->url(),
            'degrade' => $this->degrade,
            'legende' => $this->legende,
        ];
    }
}
