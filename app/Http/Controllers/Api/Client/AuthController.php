<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\LoginRequest;
use App\Http\Requests\Api\Client\RegisterClientRequest;
use App\Http\Resources\Api\Client\ClientUserResource;
use App\Models\Client;
use App\Models\User;
use App\Services\AccountMailService;
use App\Services\Audit\AuditLogService;
use App\Services\ClientMediaService;
use App\Support\Audit\AuditAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ClientApiController
{
    public function __construct(
        private readonly ClientMediaService $media,
        private readonly AccountMailService $accountMail,
        private readonly AuditLogService $audit,
    ) {}

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

            /** @var array<int, array{fichier: UploadedFile, type_document: string}> $documents */
            $documents = $data['documents'] ?? [];

            if ($documents !== []) {
                $this->media->storeDocuments($client, $documents);
            }

            return compact('user', 'client');
        });

        $result['user']->load(['client.documents']);
        $this->accountMail->notifyClientRegistered($result['user']);

        $this->audit->record(
            AuditAction::AuthClientRegister,
            [
                'user_id' => $result['user']->id,
                'client_id' => $result['client']->id,
                'email' => $result['user']->email,
            ],
            actor: $this->audit->actorFromUser($result['user']),
        );

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
            $this->audit->record(AuditAction::AuthClientLoginFailed, [
                'email' => $request->email,
            ], actor: [
                'type' => 'client',
                'id' => $user?->id,
                'name' => $user?->name,
                'email' => $request->email,
                'role' => $user?->role,
            ]);

            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        if (! $user->isClient()) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte n\'est pas autorisé à accéder à l\'espace client.'],
            ]);
        }

        $user->load(['client.documents']);

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

        $this->audit->record(
            AuditAction::AuthClientLoginSuccess,
            [
                'client_id' => $user->client?->id,
            ],
            actor: $this->audit->actorFromUser($user),
        );

        $token = $user->createToken($request->input('device_name', 'client-api'));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => ClientUserResource::make($user),
        ]);
    }

    public function me(Request $request): ClientUserResource
    {
        $request->user()->load(['client.documents']);

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
