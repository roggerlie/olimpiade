<?php

namespace Database\Factories;

use App\Models\Pelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pelajaran>
 */
class PelajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->randomElement(['Matematika', 'Bahasa Indonesia', 'IPA', 'IPS', 'Bahasa Inggris']),
        ];
    }
}
