<?php

namespace Database\Factories;

use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankSoal>
 */
class BankSoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenjang_id' => Jenjang::factory(),
            'pelajaran_id' => Pelajaran::factory(),
            'nama' => fake()->words(3, true),
            'deskripsi' => fake()->optional()->sentence(),
        ];
    }
}
