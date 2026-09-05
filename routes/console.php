<?php

use App\Jobs\DesactiverOffresDepartPassees;
use App\Services\OffreExpirationService;
use App\Services\PubliciteLifecycleService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Cron Hostinger (mutualisé / premium, sans Supervisor)
|--------------------------------------------------------------------------
|
| Un seul cron, toutes les minutes :
|
|   cd /chemin/vers/projet && php artisan schedule:run >> /dev/null 2>&1
|
| Exemple Hostinger (adapter user + domaine) :
|
|   cd /home/uXXXXXX/domains/exemple.com/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
|
| Prérequis .env : QUEUE_CONNECTION=database (déjà le cas)
|
*/

Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3 --sleep=1')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->name('queue-work-cron')
    ->description('Traite les jobs en file (mails, etc.) puis s\'arrête — adapté à un cron Hostinger');

Schedule::call(fn () => app(PubliciteLifecycleService::class)->expireOverdue())
    ->dailyAt('01:15')
    ->name('publicites-expire')
    ->description('Expire les publicités dont la date de fin est dépassée');

Schedule::job(new DesactiverOffresDepartPassees)
    ->dailyAt('23:59')
    ->timezone(OffreExpirationService::TIMEZONE)
    ->withoutOverlapping()
    ->name('offres-expire-depart')
    ->description('Désactive les offres dont la date de départ est passée (fin de journée, file database)');

Schedule::command('queue:prune-failed --hours=168')
    ->weekly()
    ->name('queue-prune-failed')
    ->description('Purge les jobs échoués de plus de 7 jours');
