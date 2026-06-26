<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\UpdatePasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordController extends ClientApiController
{
    public function update(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'password' => $request->password,
        ]);

        $currentTokenId = $user->currentAccessToken()?->id;

        $user->tokens()
            ->when($currentTokenId, fn ($query) => $query->where('id', '!=', $currentTokenId))
            ->delete();

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès.',
        ]);
    }
}
