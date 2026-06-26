<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agence\UpdatePasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
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
