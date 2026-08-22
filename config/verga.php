<?php

return [

    'mail' => [
        'admin_address' => env('MAIL_ADMIN_ADDRESS'),
    ],

    'frontend' => [
        'client_url' => env('CLIENT_FRONTEND_URL', env('APP_URL')),
        'agence_url' => env('AGENCE_FRONTEND_URL', env('APP_URL')),
        'client_password_reset_url' => env('CLIENT_FRONTEND_PASSWORD_RESET_URL'),
        'agence_password_reset_url' => env('AGENCE_FRONTEND_PASSWORD_RESET_URL'),
    ],

];
