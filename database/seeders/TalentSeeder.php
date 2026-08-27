<?php

namespace Database\Seeders;

use App\Models\Talent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Les quatre talents des maquettes, pour voir tourner l'écran d'accueil.
 *
 * Ce sont des EXEMPLES, pas de vraies personnes : les numéros sont bidon
 * et ne joignent personne. À vider avant la mise en ligne.
 *
 * Lancement :  php artisan db:seed --class=TalentSeeder
 */
class TalentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::TALENTS as $donnees) {
                $oeuvres = $donnees['oeuvres'];
                $avis = $donnees['avis'] ?? [];
                unset($donnees['oeuvres'], $donnees['avis']);

                // Un compte par talent, sinon aucun profil d'exemple ne serait
                // modifiable et on ne pourrait pas tester la connexion.
                // Mot de passe identique pour tous : ce sont des exemples.
                $utilisateur = User::updateOrCreate(
                    ['telephone' => User::normaliserTelephone($donnees['whatsapp'])],
                    ['name' => $donnees['nom'], 'password' => self::MOT_DE_PASSE],
                );

                // updateOrCreate : le seeder peut être relancé sans doublonner.
                $talent = Talent::updateOrCreate(
                    ['slug' => $donnees['slug']],
                    $donnees + ['user_id' => $utilisateur->id],
                );

                $talent->oeuvres()->delete();
                foreach ($oeuvres as $rang => $degrade) {
                    $talent->oeuvres()->create([
                        'degrade' => $degrade,
                        'ordre' => $rang,
                    ]);
                }

                $talent->avis()->delete();
                foreach ($avis as $unAvis) {
                    $talent->avis()->create($unAvis);
                }
            }
        });

        $this->command?->info(count(self::TALENTS).' talents d\'exemple en place.');
        $this->command?->warn(
            'Comptes de test, mot de passe commun : '.self::MOT_DE_PASSE
            .' — à vider avant toute mise en ligne.'
        );
    }

    /** Exemples uniquement : ces comptes ne doivent jamais atteindre la production. */
    private const MOT_DE_PASSE = 'motdepasse';

    private const TALENTS = [
        [
            'nom' => 'Awa Ngassa',
            'slug' => 'awa-ngassa',
            'metier' => 'Couture',
            'ville' => 'Douala',
            'quartier' => 'Bonapriso',
            'libre' => true,
            'prix_plancher' => 15000,
            'whatsapp' => '+237600000001',
            'note' => 4.9,
            'oeuvres' => ['violet', 'orange', 'vert', 'rose', 'jaune', 'encre'],
            'avis' => [
                [
                    'auteur' => 'Marie K.',
                    'texte' => 'Travail soigné et livré à l\'heure. Je recommande vraiment.',
                    'etoiles' => 5,
                ],
                [
                    'auteur' => 'Sandrine T.',
                    'texte' => 'Elle a compris exactement ce que je voulais dès la première fois.',
                    'etoiles' => 5,
                ],
            ],
        ],
        [
            'nom' => 'Léa Mbarga',
            'slug' => 'lea-mbarga',
            'metier' => 'Coiffure',
            'ville' => 'Yaoundé',
            'quartier' => null,
            'libre' => true,
            'prix_plancher' => 5000,
            'whatsapp' => '+237600000002',
            'oeuvres' => ['orange', 'rose'],
        ],
        [
            'nom' => 'Eric Kamga',
            'slug' => 'eric-kamga',
            'metier' => 'Cuisine',
            'ville' => 'Douala',
            'quartier' => null,
            'libre' => false,
            'prix_plancher' => null, // affiche « sur devis »
            'whatsapp' => '+237600000003',
            'oeuvres' => ['vert'],
        ],
        [
            'nom' => 'Ibrahim S.',
            'slug' => 'ibrahim-s',
            'metier' => 'Artisanat',
            'ville' => 'Bafoussam',
            'quartier' => null,
            'libre' => true,
            'prix_plancher' => 8000,
            'whatsapp' => '+237600000004',
            'oeuvres' => ['rose', 'jaune'],
        ],
    ];
}
