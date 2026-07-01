<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateConfigurationCommissionRequest;
use App\Models\ConfigurationCommission;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationCommissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/commissions/index', [
            'client' => $this->resolveConfig('client'),
            'agence' => $this->resolveConfig('agence'),
        ]);
    }

    public function update(UpdateConfigurationCommissionRequest $request, string $destinataire): RedirectResponse
    {
        abort_unless(in_array($destinataire, ['client', 'agence'], true), 404);

        $data = $request->validated();
        $data['actif'] = $request->boolean('actif');

        ConfigurationCommission::updateOrCreate(
            ['destinataire' => $destinataire],
            $data
        );

        $label = $destinataire === 'client' ? 'clients' : 'agences';

        return back()->with('success', "Configuration commission {$label} mise à jour.");
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveConfig(string $destinataire): array
    {
        $config = ConfigurationCommission::firstOrCreate(
            ['destinataire' => $destinataire],
            [
                'type' => 'pourcentage',
                'valeur' => 0,
                'actif' => false,
                'libelle' => $destinataire === 'client'
                    ? 'Commission globale clients'
                    : 'Commission globale agences',
            ]
        );

        return [
            'id' => $config->id,
            'destinataire' => $config->destinataire,
            'type' => $config->type,
            'valeur' => $config->valeur,
            'actif' => $config->actif,
            'libelle' => $config->libelle,
        ];
    }
}
