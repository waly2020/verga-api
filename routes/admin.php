<?php

use App\Http\Controllers\Admin\AgenceController;
use App\Http\Controllers\Admin\TypeAgenceController;
use App\Http\Controllers\Admin\ColisController;
use App\Http\Controllers\Admin\CollaborateurController;
use App\Http\Controllers\Admin\CommandeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OffreController;
use App\Http\Controllers\Admin\PaiementController;
use App\Http\Controllers\Admin\ReclamationController;
use App\Http\Controllers\Admin\ReversementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::post('type-agences', [TypeAgenceController::class, 'store'])->name('type-agences.store');

        Route::get('agences', [AgenceController::class, 'index'])->name('agences.index');
        Route::post('agences', [AgenceController::class, 'store'])->name('agences.store');
        Route::get('agences/{agence}', [AgenceController::class, 'show'])->name('agences.show');
        Route::patch('agences/{agence}/statut', [AgenceController::class, 'updateStatut'])->name('agences.statut');
        Route::delete('agences/{agence}', [AgenceController::class, 'destroy'])->name('agences.destroy');

        Route::get('offres', [OffreController::class, 'index'])->name('offres.index');
        Route::post('offres', [OffreController::class, 'store'])->name('offres.store');
        Route::get('commandes', [CommandeController::class, 'index'])->name('commandes.index');
        Route::get('colis', [ColisController::class, 'index'])->name('colis.index');
        Route::get('colis/{colis}', [ColisController::class, 'show'])->name('colis.show');
        Route::patch('colis/{colis}/statut', [ColisController::class, 'updateStatut'])->name('colis.statut');
        Route::get('paiements', [PaiementController::class, 'index'])->name('paiements.index');
        Route::get('reversements', [ReversementController::class, 'index'])->name('reversements.index');
        Route::patch('reversements/{reversement}/effectuer', [ReversementController::class, 'effectuer'])->name('reversements.effectuer');
        Route::get('reclamations', [ReclamationController::class, 'index'])->name('reclamations.index');
        Route::get('reclamations/{reclamation}', [ReclamationController::class, 'show'])->name('reclamations.show');
        Route::patch('reclamations/{reclamation}/statut', [ReclamationController::class, 'updateStatut'])->name('reclamations.statut');
        Route::get('collaborateurs', [CollaborateurController::class, 'index'])->name('collaborateurs.index');
        Route::get('collaborateurs/create', [CollaborateurController::class, 'create'])->name('collaborateurs.create');
        Route::post('collaborateurs', [CollaborateurController::class, 'store'])->name('collaborateurs.store');
        Route::delete('collaborateurs/{collaborateur}', [CollaborateurController::class, 'destroy'])->name('collaborateurs.destroy');
    });
