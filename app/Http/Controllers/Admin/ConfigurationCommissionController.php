<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateConfigurationCommissionRequest;
use App\Models\ConfigurationCommission;
use App\Services\CommissionPaliersService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationCommissionController extends Controller
{
    public function __construct(
        private readonly CommissionPaliersService $paliers,
    ) {}

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
        $paliers = $data['paliers'] ?? null;
        unset($data['paliers']);

        if (($data['type'] ?? '') === 'grille') {
            $data['valeur'] = 0;
        }

        DB::transaction(function () use ($destinataire, $data, $paliers): void {
            $config = ConfigurationCommission::updateOrCreate(
                ['destinataire' => $destinataire],
                $data
            );

            $config->paliers()->delete();

            if (($data['type'] ?? '') !== 'grille' || ! is_array($paliers)) {
                return;
            }

            foreach ($this->paliers->normalizeStored($paliers) as $palier) {
                $config->paliers()->create($palier);
            }
        });

        $label = $destinataire === 'client' ? 'clients' : 'agences';

        return back()->with('success', "Configuration commission {$label} mise à jour.");
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveConfig(string $destinataire): array
    {
        $config = ConfigurationCommission::query()
            ->with('paliers')
            ->firstOrCreate(
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
            'paliers' => $config->paliers
                ->map(fn ($palier) => [
                    'id' => $palier->id,
                    'montant_min' => $palier->montant_min,
                    'montant_max' => $palier->montant_max,
                    'frais' => $palier->frais,
                    'libelle' => $palier->libelle,
                ])
                ->values(),
        ];
    }
}
