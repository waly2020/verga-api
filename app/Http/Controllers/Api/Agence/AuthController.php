<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agence;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agence\LoginRequest;
use App\Http\Requests\Api\Agence\RegisterAgenceRequest;
use App\Http\Resources\Api\Agence\AgenceUserResource;
use App\Models\Agence;
use App\Models\AgenceRole;
use App\Models\AgenceUser;
use App\Services\AccountMailService;
use App\Services\AgenceMediaService;
use App\Services\Audit\AuditLogService;
use App\Support\Audit\AuditAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly AgenceMediaService $media,
        private readonly AccountMailService $accountMail,
        private readonly AuditLogService $audit,
    ) {}

    public function register(RegisterAgenceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $adminRole = AgenceRole::query()
            ->where('slug', AgenceRole::SLUG_ADMIN_AGENCE)
            ->firstOrFail();

        $result = DB::transaction(function () use ($request, $data, $adminRole) {
            $agence = Agence::create([
                'type_agence_id' => $data['type_agence_id'] ?? null,
                'nom' => $data['nom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'pays' => $data['pays'] ?? 'Gabon',
                'statut' => 'actif',
            ]);

            $agenceUser = AgenceUser::create([
                'agence_id' => $agence->id,
                'agence_role_id' => $adminRole->id,
                'name' => $data['gerant_name'],
                'email' => $data['gerant_email'],
                'telephone' => $data['telephone'],
                'password' => $data['password'],
                'statut' => AgenceUser::STATUT_ACTIF,
                'est_proprietaire' => true,
            ]);

            if ($request->hasFile('logo')) {
                /** @var UploadedFile $logo */
                $logo = $request->file('logo');
                $this->media->storeLogo($agence, $logo);
            }

            /** @var array<int, array{fichier: UploadedFile, type_document: string}> $documents */
            $documents = $data['documents'] ?? [];

            if ($documents !== []) {
                $this->media->storeDocuments($agence, $documents);
            }

            return compact('agenceUser', 'agence');
        });

        $result['agenceUser']->load(['role', 'agence.typeAgence', 'agence.logo', 'agence.documents']);
        $this->accountMail->notifyAgenceRegistered($result['agenceUser'], $result['agence']);

        $this->audit->record(
            AuditAction::AuthAgenceRegister,
            [
                'agence_id' => $result['agence']->id,
                'agence' => $result['agence']->nom,
                'user_id' => $result['agenceUser']->id,
                'email' => $result['agenceUser']->email,
            ],
            actor: $this->audit->actorFromUser($result['agenceUser']),
        );

        $token = $result['agenceUser']->createToken($data['device_name'] ?? 'agence-api');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => AgenceUserResource::make($result['agenceUser']),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $agenceUser = AgenceUser::query()->where('email', $request->email)->first();

        if (! $agenceUser || ! Hash::check($request->password, $agenceUser->password)) {
            $this->audit->record(AuditAction::AuthAgenceLoginFailed, [
                'email' => $request->email,
            ], actor: [
                'type' => 'agence',
                'id' => $agenceUser?->id,
                'name' => $agenceUser?->name,
                'email' => $request->email,
                'role' => null,
            ]);

            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        $agenceUser->load(['role', 'agence.typeAgence', 'agence.logo', 'agence.documents']);

        if (! $agenceUser->isActif()) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte est suspendu.'],
            ]);
        }

        if (! $agenceUser->agence) {
            throw ValidationException::withMessages([
                'email' => ['Aucune agence associée à ce compte.'],
            ]);
        }

        if ($agenceUser->agence->statut !== 'actif') {
            return response()->json([
                'message' => 'Ce compte agence est '.$agenceUser->agence->statut.'.',
            ], 403);
        }

        $this->audit->record(
            AuditAction::AuthAgenceLoginSuccess,
            [
                'agence_id' => $agenceUser->agence_id,
            ],
            actor: $this->audit->actorFromUser($agenceUser),
        );

        $tokenName = $request->input('device_name', 'agence-api');
        $token = $agenceUser->createToken($tokenName);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => AgenceUserResource::make($agenceUser),
        ]);
    }

    public function me(Request $request): AgenceUserResource
    {
        $request->user()->load(['role', 'agence.typeAgence', 'agence.logo', 'agence.documents']);

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
