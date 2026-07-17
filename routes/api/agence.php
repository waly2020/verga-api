<?php

/*
| API Agence — consommée par le back-office Angular (application externe)
*/

use App\Http\Controllers\Api\Agence\AuthController;
use App\Http\Controllers\Api\Agence\ColisController;
use App\Http\Controllers\Api\Agence\CommandeController;
use App\Http\Controllers\Api\Agence\DashboardController;
use App\Http\Controllers\Api\Agence\OffreController;
use App\Http\Controllers\Api\Agence\PaiementController;
use App\Http\Controllers\Api\Agence\PasswordController;
use App\Http\Controllers\Api\Agence\ReclamationController;
use App\Http\Controllers\Api\Agence\ReversementController;
use App\Http\Controllers\Api\Agence\RoleController;
use App\Http\Controllers\Api\Agence\SoldeController;
use App\Http\Controllers\Api\Agence\TypeAgenceController;
use App\Http\Controllers\Api\Agence\TypeOffreController;
use App\Http\Controllers\Api\Agence\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('agence')->name('api.agence.')->group(function () {
    Route::get('types-agences', [TypeAgenceController::class, 'index'])->name('types-agences.index');
    Route::get('types-offres', [TypeOffreController::class, 'index'])
        ->middleware('optional.sanctum')
        ->name('types-offres.index');

    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:api-agence-register')
        ->name('register');

    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:api-agence-login')
        ->name('login');

    Route::middleware(['auth:sanctum', 'agence'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{agenceUser}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{agenceUser}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('solde', SoldeController::class)->name('solde');

        Route::post('types-offres', [TypeOffreController::class, 'store'])->name('types-offres.store');
        Route::get('types-offres/{typeOffre}', [TypeOffreController::class, 'show'])->name('types-offres.show');
        Route::patch('types-offres/{typeOffre}', [TypeOffreController::class, 'update'])->name('types-offres.update');
        Route::delete('types-offres/{typeOffre}', [TypeOffreController::class, 'destroy'])->name('types-offres.destroy');

        Route::get('offres', [OffreController::class, 'index'])->name('offres.index');
        Route::post('offres', [OffreController::class, 'store'])->name('offres.store');
        Route::get('offres/{offre}', [OffreController::class, 'show'])->name('offres.show');
        Route::patch('offres/{offre}', [OffreController::class, 'update'])->name('offres.update');
        Route::delete('offres/{offre}', [OffreController::class, 'destroy'])->name('offres.destroy');

        Route::get('commandes', [CommandeController::class, 'index'])->name('commandes.index');
        Route::get('commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');
        Route::patch('commandes/{commande}/statut', [CommandeController::class, 'updateStatut'])->name('commandes.statut');

        Route::get('colis', [ColisController::class, 'index'])->name('colis.index');
        Route::get('colis/{colis}', [ColisController::class, 'show'])->name('colis.show');
        Route::patch('colis/{colis}/statut', [ColisController::class, 'updateStatut'])->name('colis.statut');

        Route::get('reclamations', [ReclamationController::class, 'index'])->name('reclamations.index');
        Route::post('reclamations', [ReclamationController::class, 'store'])->name('reclamations.store');
        Route::get('reclamations/{reclamation}', [ReclamationController::class, 'show'])->name('reclamations.show');
        Route::patch('reclamations/{reclamation}/statut', [ReclamationController::class, 'updateStatut'])->name('reclamations.statut');

        Route::get('paiements', [PaiementController::class, 'index'])->name('paiements.index');
        Route::get('reversements', [ReversementController::class, 'index'])->name('reversements.index');
    });
});
