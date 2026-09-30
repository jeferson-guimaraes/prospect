<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usuário inicial
    |--------------------------------------------------------------------------
    |
    | Dados do usuário criado pelo DatabaseSeeder. Como não há cadastro
    | público, este é o primeiro acesso ao sistema. Altere a senha no .env
    | antes de rodar o seeder fora do ambiente local.
    |
    */

    'user' => [
        'name' => env('SEED_USER_NAME', 'Administrador'),
        'email' => env('SEED_USER_EMAIL', 'admin@prospect.local'),
        'password' => env('SEED_USER_PASSWORD', 'password'),
    ],

];
