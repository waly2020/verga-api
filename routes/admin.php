<?php

use App\Http\Controllers\Admin\AgenceController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ColisController;
use App\Http\Controllers\Admin\CollaborateurController;
use App\Http\Controllers\Admin\CommandeController;
use App\Http\Controllers\Admin\ConfigurationCommissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OffreController;
use App\Http\Controllers\Admin\PaiementController;
use App\Http\Controllers\Admin\ReclamationController;
use App\Http\Controllers\Admin\ReversementController;
use App\Http\Controllers\Admin\TypeAgenceController;
use App\Http\Controllers\Admin\TypeOffreController;
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

        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');

        Route::get('offres', [OffreController::class, 'index'])->name('offres.index');
        Route::post('offres', [OffreController::class, 'store'])->name('offres.store');
        Route::patch('offres/{offre}', [OffreController::class, 'update'])->name('offres.update');
        Route::delete('offres/{offre}', [OffreController::class, 'destroy'])->name('offres.destroy');

        Route::get('types-offres', [TypeOffreController::class, 'index'])->name('types-offres.index');
        Route::post('types-offres', [TypeOffreController::class, 'store'])->name('types-offres.store');
        Route::patch('types-offres/{typeOffre}', [TypeOffreController::class, 'update'])->name('types-offres.update');
        Route::delete('types-offres/{typeOffre}', [TypeOffreController::class, 'destroy'])->name('types-offres.destroy');

        Route::get('commandes', [CommandeController::class, 'index'])->name('commandes.index');
        Route::get('commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');
        Route::get('colis', [ColisController::class, 'index'])->name('colis.index');
        Route::get('colis/{colis}', [ColisController::class, 'show'])->name('colis.show');
        Route::patch('colis/{colis}/statut', [ColisController::class, 'updateStatut'])->name('colis.statut');
        Route::get('paiements', [PaiementController::class, 'index'])->name('paiements.index');
        Route::patch('paiements/{paiement}/verifier-statut', [PaiementController::class, 'verifierStatut'])->name('paiements.verifier-statut');
        Route::get('commissions', [ConfigurationCommissionController::class, 'index'])->name('commissions.index');
        Route::patch('commissions/{destinataire}', [ConfigurationCommissionController::class, 'update'])
            ->whereIn('destinataire', ['client', 'agence'])
            ->name('commissions.update');
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
