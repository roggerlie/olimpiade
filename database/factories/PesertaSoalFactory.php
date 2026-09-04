<?php

namespace Database\Factories;

use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesertaSoal>
 */
class PesertaSoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'peserta_ujian_id' => PesertaUjian::factory(),
            'soal_id' => Soal::factory(),
            'urutan' => fake()->unique()->numberBetween(1, 50),
            'jawaban' => null,
        ];
    }
}
