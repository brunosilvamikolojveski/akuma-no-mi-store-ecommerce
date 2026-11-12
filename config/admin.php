<?php

return [
    'allow_production_seed' => env('ADMIN_SEED_ENABLED', false),
    'seed_user' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
