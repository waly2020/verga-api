<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateConfigurationPubliciteRequest;
use App\Models\ConfigurationPublicite;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationPubliciteController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/publicites/configuration', [
            'configuration' => $this->resolveConfig(),
        ]);
    }

    public function update(UpdateConfigurationPubliciteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['actif'] = $request->boolean('actif');

        $config = ConfigurationPublicite::query()->first();

        if ($config) {
            $config->update($data);
        } else {
            ConfigurationPublicite::create($data);
        }

        return back()->with('success', 'Tarif publicitaire mis à jour.');
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveConfig(): array
    {
        $config = ConfigurationPublicite::query()->first() ?? ConfigurationPublicite::create([
            'prix_par_jour' => 0,
            'type_frais' => 'pourcentage',
            'valeur_frais' => 0,
            'actif' => false,
            'libelle' => 'Tarif publicitaire VERGA',
        ]);

        return [
            'id' => $config->id,
            'prix_par_jour' => $config->prix_par_jour,
            'type_frais' => $config->type_frais,
            'valeur_frais' => $config->valeur_frais,
            'actif' => $config->actif,
            'libelle' => $config->libelle,
        ];
    }
}
