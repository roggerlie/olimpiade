<?php

namespace Database\Factories;

use App\Models\Jenjang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jenjang>
 */
class JenjangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->lexify('??'),
            'nama' => fake()->randomElement(['SD', 'SLTP', 'SLTA']),
        ];
    }
}
