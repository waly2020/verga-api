<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\StoreAgenceUserRequest;
use App\Http\Requests\Api\Agence\UpdateAgenceUserRequest;
use App\Http\Resources\Api\Agence\AgenceUserResource;
use App\Models\AgenceUser;
use App\Services\AgenceUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends AgenceApiController
{
    public function __construct(
        private readonly AgenceUserService $users,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $users = $this->agence(request())
            ->users()
            ->with('role')
            ->latest()
            ->get();

        return AgenceUserResource::collection($users);
    }

    public function store(StoreAgenceUserRequest $request): JsonResponse
    {
        $user = $this->users->create($this->agence($request), $request->validated());

        $user->load('role');

        return AgenceUserResource::make($user)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAgenceUserRequest $request, AgenceUser $agenceUser): AgenceUserResource
    {
        $this->ensureUserBelongsToAgence($request, $agenceUser);
        $this->users->update($agenceUser, $request->validated());
        $agenceUser->load('role');

        return AgenceUserResource::make($agenceUser);
    }

    public function destroy(Request $request, AgenceUser $agenceUser): JsonResponse
    {
        $this->ensureUserBelongsToAgence($request, $agenceUser);
        $this->users->delete($agenceUser);

        return response()->json([
            'message' => 'Utilisateur retiré de l\'équipe.',
        ]);
    }

    private function ensureUserBelongsToAgence(Request $request, AgenceUser $agenceUser): void
    {
        if ($agenceUser->agence_id !== $this->agence($request)->id) {
            abort(404);
        }
    }
}
