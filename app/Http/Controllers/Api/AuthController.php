<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\TalentResource;
use App\Models\Interaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/connexion
     *
     * L'inscription, elle, se fait par POST /api/talents : le talent remplit
     * un seul formulaire et repart avec son compte.
     */
    public function connexion(LoginRequest $requete): JsonResponse
    {
        $telephone = User::normaliserTelephone($requete->validated()['telephone']);
        $utilisateur = User::where('telephone', $telephone)->first();

        // Un seul message pour « numéro inconnu » et « mauvais mot de passe » :
        // sinon l'API dit aux curieux quels numéros sont inscrits.
        if (! $utilisateur || ! Hash::check($requete->validated()['password'], $utilisateur->password)) {
            throw ValidationException::withMessages([
                'telephone' => 'Numéro ou mot de passe incorrect.',
            ]);
        }

        // Un jeton par appareil, révocable séparément.
        $jeton = $utilisateur->createToken('talents')->plainTextToken;

        return response()->json([
            'token' => $jeton,
            'talent' => $utilisateur->talent
                ? new TalentResource($utilisateur->talent->load('oeuvres'))
                : null,
        ]);
    }

    /** POST /api/deconnexion — ne révoque que le jeton en cours. */
    public function deconnexion(Request $requete): JsonResponse
    {
        $requete->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'À bientôt.']);
    }

    /**
     * GET /api/moi — le profil du talent connecté, et ce qui s'y passe.
     *
     * Trois périodes plutôt qu'un total unique : « 40 vues » ne veut rien dire
     * sans repère de temps. Le talent doit pouvoir dire si sa semaine a été
     * meilleure que la précédente.
     */
    public function moi(Request $requete): JsonResponse
    {
        $talent = $requete->user()->talent;

        if (! $talent) {
            return response()->json(['talent' => null, 'activite' => null]);
        }

        $talent->load(['oeuvres', 'avis'])->loadCount('avis');

        return response()->json([
            'talent' => new TalentResource($talent),
            'activite' => [
                'semaine' => Interaction::bilan($talent, 7),
                'mois' => Interaction::bilan($talent, 30),
                'total' => Interaction::bilan($talent, null),
            ],
        ]);
    }
}
