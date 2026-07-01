<?php

return [

    'base_url' => env('BAMBOO_PAY_BASE_URL', 'https://devfront-bamboopay.ventis.group'),

    'merchant_id' => env('BAMBOO_PAY_MERCHANT_ID'),

    'username' => env('BAMBOO_PAY_USERNAME'),

    'password' => env('BAMBOO_PAY_PASSWORD'),

    'return_url' => env('BAMBOO_PAY_RETURN_URL'),

    'callback_url' => env('BAMBOO_PAY_CALLBACK_URL'),

];
