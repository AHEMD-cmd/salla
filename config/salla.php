<?php


return [

    // 'client_id'      => env('SALLA_CLIENT_ID', "30848398-9426-4144-b569-2735240eeb4e"),
    // 'client_secret'  => env('SALLA_CLIENT_SECRET', "739639809e7667e033285651598824cc "),
    'auth_url'       => env('SALLA_AUTH_URL', "https://accounts.salla.sa/oauth2/auth"),
    'token_url'      => env('SALLA_TOKEN_URL', "https://accounts.salla.sa/oauth2/token"),
    // 'callback_url'   => env('SALLA_CALLBACK_URL', "https://salla.cupun.net/callback"),
    'callback_url'   => env('APP_URL') . '/callback',
    'salla_api_url'  => env('SALLA_API_URL', "https://api.salla.dev/admin/v2"),
];
