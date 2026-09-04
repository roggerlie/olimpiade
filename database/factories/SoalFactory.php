<?php

namespace Database\Factories;

use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Soal>
 */
class SoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_soal_id' => BankSoal::factory(),
            'pertanyaan' => fake()->sentence().'?',
            'pilih_a' => fake()->words(2, true),
            'pilih_b' => fake()->words(2, true),
            'pilih_c' => fake()->words(2, true),
            'pilih_d' => fake()->words(2, true),
            'pilih_e' => null,
            'jawaban' => fake()->randomElement(['A', 'B', 'C', 'D']),
        ];
    }
}
