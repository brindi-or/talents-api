<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Interaction extends Model
{
    use HasFactory;

    /** Table nommée explicitement — la déduction donne un singulier ici. */
    protected $table = 'interactions';

    protected $fillable = ['talent_id', 'type', 'empreinte'];

    public const VUE = 'vue';
    public const CONTACT = 'contact';

    /**
     * Fenêtre de déduplication : une même personne qui rouvre la fiche dix
     * fois dans l'heure ne compte qu'une vue. Sans cela le talent verrait des
     * chiffres flatteurs et faux — et cesserait de leur faire confiance.
     */
    private const FENETRE_MINUTES = 60;

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    /**
     * Empreinte du visiteur : IP + navigateur, hachées avec la clé de l'appli.
     * On ne conserve JAMAIS l'IP en clair — elle ne sert qu'à distinguer
     * deux visiteurs, pas à les identifier.
     */
    public static function empreinte(Request $requete): string
    {
        return hash_hmac(
            'sha256',
            $requete->ip().'|'.$requete->userAgent(),
            (string) config('app.key'),
        );
    }

    /** Enregistre l'événement, sauf s'il vient d'être compté. */
    public static function noter(Talent $talent, string $type, Request $requete): void
    {
        $empreinte = self::empreinte($requete);

        $dejaCompte = self::where('talent_id', $talent->id)
            ->where('type', $type)
            ->where('empreinte', $empreinte)
            ->where('created_at', '>=', now()->subMinutes(self::FENETRE_MINUTES))
            ->exists();

        if ($dejaCompte) {
            return;
        }

        self::create([
            'talent_id' => $talent->id,
            'type' => $type,
            'empreinte' => $empreinte,
        ]);
    }

    public function scopeDepuis(Builder $requete, ?int $jours): Builder
    {
        return $jours === null
            ? $requete
            : $requete->where('created_at', '>=', now()->subDays($jours));
    }

    /**
     * Compte les vues et les contacts d'un talent sur une période.
     * Une seule requête pour les deux types : le tableau de bord en affiche
     * plusieurs périodes, autant ne pas multiplier les allers-retours.
     */
    public static function bilan(Talent $talent, ?int $jours = null): array
    {
        $lignes = self::where('talent_id', $talent->id)
            ->depuis($jours)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'vues' => (int) ($lignes[self::VUE] ?? 0),
            'contacts' => (int) ($lignes[self::CONTACT] ?? 0),
        ];
    }
}
