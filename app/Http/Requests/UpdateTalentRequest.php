<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Modification d'un profil existant.
 *
 * Toutes les regles sont en « sometimes » : le talent doit pouvoir ne changer
 * QUE sa disponibilite depuis son telephone, sans renvoyer tout son profil.
 * L'autorisation est portee par la TalentPolicy, pas ici.
 */
class UpdateTalentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'min:2', 'max:80'],
            'metier' => ['sometimes', Rule::in(StoreTalentRequest::METIERS)],
            'ville' => ['sometimes', 'string', 'max:60'],
            'quartier' => ['sometimes', 'nullable', 'string', 'max:60'],
            'libre' => ['sometimes', 'boolean'],
            'prixPlancher' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000000'],
            'whatsapp' => ['sometimes', 'string', 'regex:/^\+?[0-9 ]{9,20}$/'],
            'videoUrl' => ['sometimes', 'nullable', 'url', 'max:255'],
        ];
    }
}
