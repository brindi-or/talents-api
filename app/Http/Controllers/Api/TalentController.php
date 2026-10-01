<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTalentRequest;
use App\Http\Requests\UpdateTalentRequest;
use App\Http\Resources\TalentResource;
use App\Models\Oeuvre;
use App\Models\Interaction;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TalentController extends Controller
{
    /** GET /api/talents — la grille d'accueil, filtrée. */
    public function index(Request $requete): AnonymousResourceCollection
    {
        $talents = Talent::query()
            ->recherche($requete->query('q'))
            ->metier($requete->query('metier'))
            // Les talents libres d'abord : c'est ce que le client cherche.
            ->orderByDesc('libre')
            ->orderBy('nom')
            // Une seule œuvre suffit pour la vignette de la carte.
            ->with(['oeuvres' => fn ($q) => $q->limit(1)])
            ->withCount('avis')
            ->paginate(24)
            ->withQueryString();

        return TalentResource::collection($talents);
    }

    /** GET /api/talents/{slug} — la fiche complète. */
    public function show(Request $requete, Talent $talent): TalentResource
    {
        // Une vue de plus pour le tableau de bord du talent. Dédupliquée
        // par visiteur sur une heure : rafraîchir la page ne gonfle pas
        // le compteur.
        Interaction::noter($talent, Interaction::VUE, $requete);

        $talent->load(['oeuvres', 'avis'])->loadCount('avis');

        return new TalentResource($talent);
    }

    /**
     * POST /api/talents — l'inscription en 3 étapes.
     *
     * Crée le compte ET le profil en une fois : le talent ne remplit qu'un
     * formulaire, et repart avec un jeton pour revenir modifier son profil.
     */
    public function store(StoreTalentRequest $requete): JsonResponse
    {
        $valide = $requete->validated();
        $telephone = User::normaliserTelephone($valide['whatsapp']);

        // Vérifié ici et pas seulement par la contrainte unique : sinon le
        // talent reçoit une erreur SQL illisible au lieu d'un message clair.
        if (User::where('telephone', $telephone)->exists()) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Ce numéro a déjà un profil. Connectez-vous pour le modifier.',
            ]);
        }

        // Transaction : un compte sans profil, ou un profil sans ses œuvres,
        // laisserait le talent coincé à mi-chemin.
        [$talent, $jeton] = DB::transaction(function () use ($valide, $telephone) {
            $utilisateur = User::create([
                'name' => $valide['nom'],
                'telephone' => $telephone,
                'password' => $valide['password'], // haché par le cast du modèle
            ]);

            $talent = $utilisateur->talent()->create([
                'nom' => $valide['nom'],
                'slug' => Talent::slugUnique($valide['nom']),
                'metier' => $valide['metier'],
                'ville' => $valide['ville'],
                'quartier' => $valide['quartier'] ?? null,
                'libre' => $valide['libre'],
                'prix_plancher' => $valide['prixPlancher'] ?? null,
                'whatsapp' => $valide['whatsapp'],
                'video_url' => $valide['videoUrl'] ?? null,
            ]);

             // Les vrais fichiers d'abord : ce sont eux qui comptent.
            foreach ($requete->file('fichiers') ?? [] as $fichier) {
                Oeuvre::depuisFichier($talent, $fichier);
            }

            foreach ($valide['oeuvres'] ?? [] as $rang => $oeuvre) {
                $talent->oeuvres()->create([
                    'degrade' => $oeuvre['degrade'] ?? null,
                    'legende' => $oeuvre['legende'] ?? null,
                    'ordre' => $rang,
                ]);
            }

            return [$talent, $utilisateur->createToken('talents')->plainTextToken];
        });

        $talent->load('oeuvres');

        return response()->json([
            'token' => $jeton,
            'talent' => new TalentResource($talent),
        ], 201);
    }

    /**
     * PUT /api/talents/{slug} — le talent modifie son profil.
     *
     * Protégé par la TalentPolicy : chacun le sien.
     */
    public function update(UpdateTalentRequest $requete, Talent $talent): TalentResource
    {
        // Laravel 11+ : le Controller de base ne porte plus AuthorizesRequests,
        // donc pas de $this->authorize(). Gate::authorize fait le meme travail.
        Gate::authorize('update', $talent);

        $valide = $requete->validated();

        // Le front parle en camelCase, la base en snake_case.
        $champs = array_filter([
            'nom' => $valide['nom'] ?? null,
            'metier' => $valide['metier'] ?? null,
            'ville' => $valide['ville'] ?? null,
            'whatsapp' => $valide['whatsapp'] ?? null,
        ], fn ($valeur) => $valeur !== null);

        // Traités à part : null est une valeur VALIDE pour ces trois-là
        // (« sur devis », pas de quartier, pas de vidéo). array_filter les
        // aurait silencieusement ignorés.
        foreach (['quartier' => 'quartier', 'prixPlancher' => 'prix_plancher', 'videoUrl' => 'video_url'] as $entree => $colonne) {
            if ($requete->has($entree)) {
                $champs[$colonne] = $valide[$entree];
            }
        }

        if ($requete->has('libre')) {
            $champs['libre'] = $valide['libre'];
        }

        $talent->update($champs);

        return new TalentResource($talent->load('oeuvres'));
    }

    /**
     * GET /api/talents/{slug}/contact — le lien WhatsApp.
     *
     * Un endpoint à part, exprès : le numéro ne doit pas se promener dans
     * les réponses de liste, où il serait aspiré en masse. Ici, on le
     * délivre un talent à la fois, et on saura compter les mises en relation.
     */
    public function contact(Request $requete, Talent $talent): JsonResponse
    {
        // La mise en relation, comptée ici et nulle part ailleurs : c'est le
        // seul endroit où le numéro sort, donc le seul chiffre qui compte
        // vraiment pour le talent.
        Interaction::noter($talent, Interaction::CONTACT, $requete);

        return response()->json([
            'lien' => $talent->lienWhatsapp(),
        ]);
    }
}
