<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agence\ForgotPasswordRequest;
use App\Http\Requests\Api\Agence\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordReset,
    ) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordReset->sendAgenceResetLink($request->validated());

        return response()->json([
            'message' => 'Si un compte agence existe pour cette adresse, un e-mail de réinitialisation a été envoyé.',
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->passwordReset->resetAgencePassword($request->validated());

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Impossible de réinitialiser le mot de passe.',
                'errors' => [
                    'email' => [__($status)],
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès.',
        ]);
    }
}
