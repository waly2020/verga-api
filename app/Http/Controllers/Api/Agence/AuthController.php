<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agence\LoginRequest;
use App\Http\Requests\Api\Agence\RegisterAgenceRequest;
use App\Http\Resources\Api\Agence\AgenceUserResource;
use App\Models\Agence;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterAgenceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['gerant_name'],
                'email' => $data['gerant_email'],
                'telephone' => $data['telephone'],
                'password' => $data['password'],
                'role' => 'agence',
            ]);

            $agence = Agence::create([
                'user_id' => $user->id,
                'type_agence_id' => $data['type_agence_id'] ?? null,
                'nom' => $data['nom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'pays' => $data['pays'] ?? 'Gabon',
                'statut' => 'actif',
            ]);

            return compact('user', 'agence');
        });

        $result['user']->load('agence.typeAgence');
        $token = $result['user']->createToken($data['device_name'] ?? 'agence-api');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => AgenceUserResource::make($result['user']),
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

        if (! $user->isAgence()) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte n\'est pas autorisé à accéder à l\'espace agence.'],
            ]);
        }

        $user->load('agence.typeAgence');

        if (! $user->agence) {
            throw ValidationException::withMessages([
                'email' => ['Aucune agence associée à ce compte.'],
            ]);
        }

        if ($user->agence->statut !== 'actif') {
            return response()->json([
                'message' => 'Ce compte agence est '.$user->agence->statut.'.',
            ], 403);
        }

        $tokenName = $request->input('device_name', 'agence-api');
        $token = $user->createToken($tokenName);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => AgenceUserResource::make($user),
        ]);
    }

    public function me(Request $request): AgenceUserResource
    {
        $request->user()->load('agence.typeAgence');

        return AgenceUserResource::make($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}
