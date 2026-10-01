<?php

use App\Http\Controllers\Api\OeuvreController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TalentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes de l'API Talents
|--------------------------------------------------------------------------
|
| ⚠️ Sur Laravel 11 et plus, ce fichier n'existe pas dans un projet neuf.
| Lance d'abord :   php artisan install:api
| Sinon ces routes ne seront jamais chargées, et tu chercheras longtemps.
| (Cette commande installe aussi Sanctum, dont dépend l'authentification.)
|
*/

// ---------------------------------------------------------------- public
Route::get('talents', [TalentController::class, 'index']);

// Avant la route {talent}, sinon « contact » serait pris pour un slug.
Route::get('talents/{talent}/contact', [TalentController::class, 'contact']);
Route::get('talents/{talent}', [TalentController::class, 'show']);

// ------------------------------------------------- ouvert mais rationné
// Sans compte requis, rien n'empêcherait de créer mille profils ni de
// tester des mots de passe en boucle. Le débit est la seule barrière ici.
Route::middleware('throttle:5,1')->group(function () {
    Route::post('talents', [TalentController::class, 'store']);
    Route::post('connexion', [AuthController::class, 'connexion']);
});

// ------------------------------------------------------------ connecté
Route::middleware('auth:sanctum')->group(function () {
    Route::get('moi', [AuthController::class, 'moi']);
    Route::post('deconnexion', [AuthController::class, 'deconnexion']);

    // La TalentPolicy vérifie en plus que c'est bien SON profil.
    Route::put('talents/{talent}', [TalentController::class, 'update']);

    // Les œuvres : un fichier par appel. En 3G, un envoi groupé qui échoue
    // à la cinquième photo perd les quatre premières.
    Route::post('talents/{talent}/oeuvres', [OeuvreController::class, 'store']);
    Route::delete('talents/{talent}/oeuvres/{oeuvre}', [OeuvreController::class, 'destroy']);
});
