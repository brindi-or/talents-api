<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Talent extends Model
{
    use HasFactory;

    /**
     * Table nommée EXPLICITEMENT.
     *
     * Sur l'installation de Brinda, Eloquent déduisait « talent » au singulier
     * et cherchait une table qui n'existe pas. Même cause que les clés
     * étrangères : on ne laisse plus Laravel deviner un nom de table ici.
     */
    protected $table = 'talents';

    protected $fillable = [
        'nom',
        'slug',
        'metier',
        'ville',
        'quartier',
        'libre',
        'prix_plancher',
        'whatsapp',
        'video_url',

        // Écrits par le système, jamais par un formulaire : ni UpdateTalentRequest
        // ni StoreTalentRequest ne les valident, donc ils ne peuvent pas arriver
        // d'une requête. Ils sont ici pour que le seeder puisse les poser.
        'user_id',
        'note',
    ];

    /**
     * ⚠️ Le numéro ne sort JAMAIS dans une réponse JSON.
     *
     * C'est une promesse faite au talent au moment de son inscription :
     * « votre numéro reste masqué jusqu'au premier message du client ».
     * Le masquer ici protège même les endroits où l'on oublierait la Resource.
     * Pour joindre un talent, passer par /talents/{talent}/contact.
     */
    protected $hidden = ['whatsapp'];

    protected function casts(): array
    {
        return [
            'libre' => 'boolean',
            'prix_plancher' => 'integer',
            'note' => 'float',
        ];
    }

    public function oeuvres(): HasMany
    {
        return $this->hasMany(Oeuvre::class)->orderBy('ordre');
    }

    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class)->latest();
    }

    /** Vues et mises en relation — ce que le talent voit dans son espace. */
    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    /** L'URL utilise le slug, pas l'id. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Génère un slug unique : « Awa Ngassa » puis « awa-ngassa-2 » si déjà pris. */
    public static function slugUnique(string $nom): string
    {
        $base = Str::slug($nom);
        $slug = $base;
        $suffixe = 1;

        while (static::where('slug', $slug)->exists()) {
            $suffixe++;
            $slug = "{$base}-{$suffixe}";
        }

        return $slug;
    }

    /** Recherche libre sur le nom, le métier, la ville et le quartier. */
    public function scopeRecherche(Builder $requete, ?string $terme): Builder
    {
        if (blank($terme)) {
            return $requete;
        }

        $motif = '%'.str_replace('%', '\%', $terme).'%';

        return $requete->where(function (Builder $q) use ($motif) {
            $q->where('nom', 'like', $motif)
                ->orWhere('metier', 'like', $motif)
                ->orWhere('ville', 'like', $motif)
                ->orWhere('quartier', 'like', $motif);
        });
    }

    public function scopeMetier(Builder $requete, ?string $metier): Builder
    {
        return blank($metier) ? $requete : $requete->where('metier', $metier);
    }

    /** Lien WhatsApp avec un premier message déjà écrit. */
    public function lienWhatsapp(): string
    {
        $numero = preg_replace('/\D/', '', $this->whatsapp);
        $texte = rawurlencode(
            "Bonjour {$this->nom}, je vous ai trouvé(e) sur Talents. J'aimerais discuter d'un projet."
        );

        return "https://wa.me/{$numero}?text={$texte}";
    }
}
