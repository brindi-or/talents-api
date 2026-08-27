# API Talents — fichiers à déposer dans un Laravel neuf

Ces fichiers ne forment pas un projet à eux seuls : ils se déposent dans un
squelette Laravel que tu génères toi-même. C'est voulu — je n'ai ni PHP ni
Composer sur mon poste, donc **rien de tout ceci n'a été exécuté**. En te
laissant créer le squelette, la partie non testée reste la plus petite possible.

## 1. Créer le projet

```bash
composer create-project laravel/laravel talents-api
cd talents-api
php artisan install:api
```

⚠️ **`php artisan install:api` n'est pas optionnel** sur Laravel 11+ : sans lui,
`routes/api.php` n'existe pas et n'est pas chargé. Tes routes répondraient 404
sans que rien n'explique pourquoi. Cette commande installe aussi **Sanctum**,
dont dépend toute l'authentification ci-dessous.

## 2. Copier les fichiers

Respecte l'arborescence, elle correspond déjà à celle de Laravel :

```
app/Http/Controllers/Api/{TalentController,AuthController}.php
app/Http/Requests/{StoreTalentRequest,UpdateTalentRequest,LoginRequest}.php
app/Http/Resources/{TalentResource,OeuvreResource,AvisResource}.php
app/Models/{Talent,Oeuvre,Avis,User}.php     ← User ÉCRASE celui de Laravel
app/Policies/TalentPolicy.php
database/migrations/2026_08_22_0000*.php
database/seeders/TalentSeeder.php
routes/api.php                                ← écrase celui d'install:api
```

## 3. Lancer

```bash
php artisan migrate
php artisan db:seed --class=TalentSeeder
php artisan serve
```

Par défaut Laravel utilise SQLite : rien à installer. Si tu préfères MySQL,
change `DB_CONNECTION` dans `.env`.

Les 4 talents d'exemple ont tous le mot de passe **`motdepasse`**, et leur
numéro sert d'identifiant. Ce sont des données de test : **à vider avant toute
mise en ligne.**

## Les routes

| Méthode | Route | Accès | Ce que ça fait |
|---|---|---|---|
| GET | `/api/talents` | public | La grille d'accueil. Paramètres `q` et `metier`. Paginé par 24. |
| GET | `/api/talents/{slug}` | public | La fiche complète : œuvres, avis, tarif. |
| GET | `/api/talents/{slug}/contact` | public | Le lien WhatsApp, un talent à la fois. |
| POST | `/api/talents` | 5/min | Inscription : crée le compte **et** le profil, renvoie un jeton. |
| POST | `/api/connexion` | 5/min | Renvoie un jeton. |
| GET | `/api/moi` | connecté | Le profil du talent connecté. |
| PUT | `/api/talents/{slug}` | **propriétaire** | Modifier son profil. |
| POST | `/api/deconnexion` | connecté | Révoque le jeton courant. |

L'URL utilise le **slug**, pas l'id : `/api/talents/awa-ngassa`.
Les routes connectées attendent l'en-tête `Authorization: Bearer <jeton>`.

## L'authentification, telle qu'elle est faite

**On se connecte avec son téléphone, pas avec un email.** Les talents ont tous
un numéro WhatsApp ; beaucoup n'ont pas d'adresse mail. La colonne `email` de
Laravel devient donc facultative.

**Un seul formulaire.** Le talent remplit ses 3 étapes, choisit un mot de passe,
et repart avec son compte et son jeton. Pas d'écran d'inscription séparé — il y
en a déjà bien assez entre lui et sa mise en ligne.

**Le numéro est normalisé** (chiffres seuls) avant d'être stocké comme
identifiant : `+237 600 00 00 01` et `237600000001` désignent le même compte.
Sans ça, un talent se recrée un compte à chaque faute de frappe.

**Un talent ne modifie que le sien** — `TalentPolicy`. Un profil sans `user_id`
(créé avant les comptes) n'est modifiable par personne tant qu'il n'a pas été
rattaché : le comparer à `null` laisserait n'importe qui l'éditer.

**Message d'erreur unique à la connexion** : « Numéro ou mot de passe
incorrect ». Distinguer les deux cas dirait aux curieux quels numéros sont
inscrits.

## ⚠️ Le point à ne pas casser

**Le numéro WhatsApp ne sort jamais dans une réponse de liste ou de fiche.**

C'est promis au talent à l'inscription : « votre numéro reste masqué jusqu'à
leur premier message ». La promesse est tenue à trois endroits, exprès :

1. `$hidden = ['whatsapp']` sur le modèle — protège même si on oublie la Resource ;
2. `TalentResource` ne mentionne pas le champ ;
3. le lien ne s'obtient que par `/contact`, un talent à la fois.

Sans ce troisième point, un `GET /api/talents` suffirait à aspirer les numéros
de tout l'annuaire d'un coup. Et ça donne, en prime, de quoi compter les vraies
mises en relation.

## Ce qui n'est pas fait

Choix assumés, pas des oublis :

- **Pas d'upload de photo.** La table `oeuvres` a déjà la colonne `chemin` qui
  l'attend ; en attendant, `degrade` tient la place de l'image.
- **Pas de mot de passe oublié.** C'est le vrai manque de cette version : un
  talent qui oublie le sien est bloqué. Le réparer demande soit un SMS (donc un
  fournisseur à payer), soit un email — que la plupart n'ont pas. À trancher.
- **Pas de suppression de profil.** La policy l'autorise déjà, la route n'existe
  pas encore.
- **Pas de tests.** À écrire quand les endpoints auront cessé de bouger.

## Côté front

Le front Angular attend déjà ces noms de champs (`prixPlancher`, `videoUrl`,
`oeuvres[].degrade`). Deux choses à y ajouter maintenant :

1. un champ **mot de passe** à l'étape 3 du parcours de création — la maquette
   validée ne l'avait pas ;
2. le stockage du **jeton** renvoyé par l'inscription et la connexion, puis son
   envoi en en-tête sur les routes protégées.
