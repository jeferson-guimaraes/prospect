<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Como o cadastro público está desabilitado, o usuário inicial é criado
     * aqui a partir das variáveis SEED_USER_* do .env.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => config('seed.user.email')],
            [
                'name' => config('seed.user.name'),
                'password' => config('seed.user.password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
