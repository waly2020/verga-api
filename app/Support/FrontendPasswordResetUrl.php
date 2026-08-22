<?php

namespace App\Support;

class FrontendPasswordResetUrl
{
    public static function client(string $token, string $email): string
    {
        return self::build(
            config('verga.frontend.client_password_reset_url')
                ?? rtrim((string) config('verga.frontend.client_url'), '/').'/reset-password',
            $token,
            $email,
        );
    }

    public static function agence(string $token, string $email): string
    {
        return self::build(
            config('verga.frontend.agence_password_reset_url')
                ?? rtrim((string) config('verga.frontend.agence_url'), '/').'/reset-password',
            $token,
            $email,
        );
    }

    private static function build(string $baseUrl, string $token, string $email): string
    {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.http_build_query([
            'token' => $token,
            'email' => $email,
        ]);
    }
}
