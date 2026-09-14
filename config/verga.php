<?php

return [

    'mail' => [
        'admin_address' => env('MAIL_ADMIN_ADDRESS'),
    ],

    'audit' => [
        'disk' => env('AUDIT_DISK', 'audit'),
        'timezone' => 'Africa/Libreville',
        'sensitive_keys' => [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'plain_text_token',
            'authorization',
            'secret',
            'two_factor',
            'recovery',
            'cvv',
            'pin',
            'otp',
            'card',
        ],
    ],

    'frontend' => [
        'client_url' => env('CLIENT_FRONTEND_URL', 'https://verga-export.com'),
        'agence_url' => env('AGENCE_FRONTEND_URL', 'https://verga-export.com'),
        'client_login_url' => env('CLIENT_FRONTEND_LOGIN_URL', 'https://verga-export.com/connexion'),
        'agence_login_url' => env('AGENCE_FRONTEND_LOGIN_URL', 'https://verga-export.com/connexion'),
        'client_password_reset_url' => env('CLIENT_FRONTEND_PASSWORD_RESET_URL'),
        'agence_password_reset_url' => env('AGENCE_FRONTEND_PASSWORD_RESET_URL'),
    ],

];
