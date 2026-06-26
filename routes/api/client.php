<?php

/*
| API Client — consommée par l'application client externe (mobile / web)
|
| Création et mise à jour des comptes client : uniquement via cette API.
| Le back-office admin VERGA consulte les clients en lecture seule (web).
*/

use App\Http\Controllers\Api\Client\AuthController;
use App\Http\Controllers\Api\Client\ColisController;
use App\Http\Controllers\Api\Client\CommandeController;
use App\Http\Controllers\Api\Client\PaiementController;
use App\Http\Controllers\Api\Client\PasswordController;
use App\Http\Controllers\Api\Client\ProfileController;
use App\Http\Controllers\Api\Client\ReclamationController;
use Illuminate\Support\Facades\Route;

Route::prefix('client')->name('api.client.')->group(function () {
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:api-client-register')
        ->name('register');

    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:api-client-login')
        ->name('login');

    Route::middleware(['auth:sanctum', 'client'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::get('commandes', [CommandeController::class, 'index'])->name('commandes.index');
        Route::get('commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');

        Route::get('colis', [ColisController::class, 'index'])->name('colis.index');
        Route::get('colis/{colis}', [ColisController::class, 'show'])->name('colis.show');

        Route::get('paiements', [PaiementController::class, 'index'])->name('paiements.index');

        Route::get('reclamations', [ReclamationController::class, 'index'])->name('reclamations.index');
        Route::post('reclamations', [ReclamationController::class, 'store'])->name('reclamations.store');
        Route::get('reclamations/{reclamation}', [ReclamationController::class, 'show'])->name('reclamations.show');
    });
});
