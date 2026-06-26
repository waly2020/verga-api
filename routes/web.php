<?php

/*
|--------------------------------------------------------------------------
| Routes WEB — Applications internes
|--------------------------------------------------------------------------
|
| Back-office admin VERGA (Inertia + React, ce dépôt).
| Auth session Fortify · middleware admin · pages /admin/*
|
| ⚠ Les apps externes (back-office agence Angular, app client) passent
|   par routes/api.php — ne pas ajouter leurs endpoints ici.
|
*/

use App\Models\User;
use Illuminate\Support\Facades\Route;

if (app()->isLocal()) {
    Route::get('/dev/login-admin', function () {
        $user = User::firstOrCreate(
            ['email' => 'admin@verga.test'],
            [
                'name' => 'Admin VERGA',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        auth()->login($user);

        return redirect('/admin/dashboard');
    })->name('dev.login-admin');
}

Route::redirect('/', '/admin/dashboard')->name('home');
Route::redirect('/dashboard', '/admin/dashboard')->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
