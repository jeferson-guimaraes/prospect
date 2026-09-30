<?php

namespace Database\Factories;

use App\Enums\CanalContato;
use App\Enums\RetornoContato;
use App\Models\Prospeccao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospeccao>
 */
class ProspeccaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'nome' => fake()->name(),
            'possiveis_dores' => fake()->sentence(),
            'contato_responsavel' => fake()->name(),
            'whatsapp' => fake()->phoneNumber(),
            'instagram' => fake()->userName(),
            'email' => fake()->email(),
            'data_contato' => fake()->date(),
            'canal_contato' => fake()->randomElement(CanalContato::cases()),
            'data_resposta' => fake()->date(),
            'retorno' => fake()->randomElement(RetornoContato::cases()),
        ];
    }
}
