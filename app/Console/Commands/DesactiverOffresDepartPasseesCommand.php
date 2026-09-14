<?php

namespace App\Console\Commands;

use App\Services\OffreExpirationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('offres:desactiver-depart-passes')]
#[Description('Désactive les offres actives dont la date de départ est aujourd’hui ou déjà passée')]
class DesactiverOffresDepartPasseesCommand extends Command
{
    public function handle(OffreExpirationService $expiration): int
    {
        $count = $expiration->desactiverDepartPasses();

        $this->components->info("{$count} offre(s) désactivée(s).");

        return self::SUCCESS;
    }
}
