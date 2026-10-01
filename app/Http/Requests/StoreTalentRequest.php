<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTalentRequest extends FormRequest
{
    /** Ouvert pour l'instant : l'inscription d'un talent ne demande pas de compte. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Étape 1
            'nom' => ['required', 'string', 'min:2', 'max:80'],
            'metier' => ['required', Rule::in(self::METIERS)],
            'ville' => ['required', 'string', 'max:60'],
            'quartier' => ['nullable', 'string', 'max:60'],

            // Étape 2 — facultative : « Je le ferai plus tard » existe dans le parcours.
            // Deux formes acceptées : de VRAIS fichiers (fichiers[]), ou des
            // dégradés de démonstration quand le talent n'a pas encore de photo.
            'fichiers' => ['sometimes', 'array', 'max:6'],
            'fichiers.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,heic,mp4,mov,webm',
                'max:'.StoreOeuvreRequest::TAILLE_MAX_KO,
            ],

            'oeuvres' => ['sometimes', 'array', 'max:6'],
            'oeuvres.*.degrade' => ['nullable', Rule::in(self::DEGRADES)],
            'oeuvres.*.legende' => ['nullable', 'string', 'max:120'],

            // Étape 3
            'libre' => ['required', 'boolean'],
            'prixPlancher' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'whatsapp' => ['required', 'string', 'regex:/^\+?[0-9 ]{9,20}$/'],
            'videoUrl' => ['nullable', 'url', 'max:255'],

            // Le compte se crée en même temps que le profil : le talent ne
            // remplit qu'un seul formulaire. Le numéro saisi ci-dessus lui
            // sert d'identifiant de connexion.
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Il faut un nom pour vous trouver.',
            'metier.in' => 'Choisissez un métier dans la liste.',
            'ville.required' => 'Indiquez au moins votre ville.',
            'whatsapp.required' => 'Sans numéro, les clients ne peuvent pas vous joindre.',
            'whatsapp.regex' => 'Ce numéro ne ressemble pas à un numéro de téléphone.',
            'oeuvres.max' => 'Six œuvres au maximum pour l\'instant.',
            'fichiers.max' => 'Six fichiers au maximum pour commencer.',
            'fichiers.*.mimes' => 'Formats acceptés : JPG, PNG, WEBP, HEIC, MP4, MOV, WEBM.',
            'fichiers.*.max' => 'Un fichier dépasse 20 Mo.',
            'password.required' => 'Choisissez un mot de passe pour revenir modifier votre profil.',
            'password.min' => 'Six caractères au minimum.',
            'password.confirmed' => 'Les deux mots de passe ne sont pas les mêmes.',
        ];
    }

    public const METIERS = [
        'Couture',
        'Coiffure',
        'Cuisine',
        'Artisanat',
        'Menuiserie',
        'Autre',
    ];

    public const DEGRADES = ['violet', 'orange', 'vert', 'rose', 'jaune', 'encre'];
}
