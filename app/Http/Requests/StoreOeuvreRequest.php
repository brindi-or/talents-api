<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ajout d'une photo ou d'une vidéo au catalogue d'un talent.
 *
 * Les limites sont pensées pour le Cameroun : beaucoup de talents publieront
 * depuis un téléphone, en 3G, avec un forfait compté. Mieux vaut refuser tôt
 * et clairement qu'accepter un envoi de 80 Mo qui échouera au bout de dix
 * minutes.
 */
class StoreOeuvreRequest extends FormRequest
{
    /** L'autorisation est portée par la TalentPolicy dans le contrôleur. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fichier' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,heic,mp4,mov,webm',
                'max:'.self::TAILLE_MAX_KO,
            ],
            'legende' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'fichier.required' => 'Choisissez une photo ou une vidéo.',
            'fichier.mimes' => 'Formats acceptés : JPG, PNG, WEBP, HEIC pour les photos ; MP4, MOV, WEBM pour les vidéos.',
            'fichier.max' => 'Fichier trop lourd : 20 Mo au maximum.',
        ];
    }

    /** 20 Mo — assez pour une photo de téléphone ou une vidéo de 30 s. */
    public const TAILLE_MAX_KO = 20480;
}
