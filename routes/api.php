<?php

/*
|--------------------------------------------------------------------------
| Routes API — Applications externes
|--------------------------------------------------------------------------
|
| Consommées par des apps hors de ce dépôt :
|   • Back-office agence (Angular)
|   • App client mobile / web (futur)
|   • Webhooks et intégrations tierces
|
| Auth Sanctum (Bearer token) · réponses JSON uniquement.
|
| ⚠ Le back-office admin VERGA (React) utilise routes/web.php — ne pas
|   dupliquer ses fonctionnalités ici.
|
*/

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/agence.php';
    require __DIR__.'/api/client.php';
    require __DIR__.'/api/payments.php';
});
