<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\UpdateClientRequest;
use App\Http\Resources\Api\Client\ClientUserResource;

class ProfileController extends ClientApiController
{
    public function update(UpdateClientRequest $request): ClientUserResource
    {
        $user = $request->user();
        $client = $this->client($request);
        $data = $request->validated();

        $client->update($data);

        if (isset($data['email']) || isset($data['telephone']) || isset($data['nom']) || isset($data['prenom'])) {
            $user->update([
                'email' => $data['email'] ?? $user->email,
                'telephone' => $data['telephone'] ?? $user->telephone,
                'name' => trim(($data['prenom'] ?? $client->prenom).' '.($data['nom'] ?? $client->nom)),
            ]);
        }

        $user->load(['client.documents']);

        return ClientUserResource::make($user);
    }
}
