<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\LoginRequest;
use App\Http\Requests\Api\Client\RegisterClientRequest;
use App\Http\Resources\Api\Client\ClientUserResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ClientApiController
{
    public function register(RegisterClientRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim("{$data['prenom']} {$data['nom']}"),
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'password' => $data['password'],
                'role' => 'client',
            ]);

            $client = Client::create([
                'user_id' => $user->id,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'pays' => $data['pays'] ?? 'Gabon',
                'type' => $data['type'] ?? 'particulier',
                'statut' => 'actif',
            ]);

            return compact('user', 'client');
        });

        $result['user']->load('client');
        $token = $result['user']->createToken($data['device_name'] ?? 'client-api');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => ClientUserResource::make($result['user']),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        if (! $user->isClient()) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte n\'est pas autorisé à accéder à l\'espace client.'],
            ]);
        }

        $user->load('client');

        if (! $user->client) {
            throw ValidationException::withMessages([
                'email' => ['Aucun profil client associé à ce compte.'],
            ]);
        }

        if ($user->client->statut !== 'actif') {
            return response()->json([
                'message' => 'Ce compte client est '.$user->client->statut.'.',
            ], 403);
        }

        $token = $user->createToken($request->input('device_name', 'client-api'));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => ClientUserResource::make($user),
        ]);
    }

    public function me(Request $request): ClientUserResource
    {
        $request->user()->load('client');

        return ClientUserResource::make($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}
