<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOeuvreRequest;
use App\Http\Resources\OeuvreResource;
use App\Models\Oeuvre;
use App\Models\Talent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class OeuvreController extends Controller
{
    /** Au-delà, la fiche devient un catalogue illisible et le disque enfle. */
    private const MAXIMUM_PAR_TALENT = 12;

    /**
     * POST /api/talents/{slug}/oeuvres — ajouter une photo ou une vidéo.
     *
     * Un fichier à la fois, volontairement : en 3G, un envoi groupé qui
     * échoue à la cinquième photo perd les quatre premières. Le front boucle.
     */
    public function store(StoreOeuvreRequest $requete, Talent $talent): JsonResponse
    {
        Gate::authorize('update', $talent);

        if ($talent->oeuvres()->count() >= self::MAXIMUM_PAR_TALENT) {
            return response()->json([
                'message' => 'Vous avez atteint '.self::MAXIMUM_PAR_TALENT
                    .' œuvres. Retirez-en une avant d’en ajouter une autre.',
            ], 422);
        }

        $oeuvre = Oeuvre::depuisFichier(
            $talent,
            $requete->file('fichier'),
            $requete->input('legende'),
        );

        return (new OeuvreResource($oeuvre))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * DELETE /api/talents/{slug}/oeuvres/{oeuvre} — retirer une œuvre.
     *
     * L'œuvre est cherchée DANS le talent, pas globalement : sans cela,
     * connaître un id suffirait à supprimer l'œuvre de quelqu'un d'autre.
     */
    public function destroy(Talent $talent, int $oeuvre): JsonResponse
    {
        Gate::authorize('update', $talent);

        $cible = $talent->oeuvres()->findOrFail($oeuvre);
        $cible->supprimerAvecFichier();

        return response()->json(['message' => 'Œuvre retirée.']);
    }
}
