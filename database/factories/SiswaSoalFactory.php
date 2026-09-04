<?php

namespace Database\Factories;

use App\Models\SiswaSoal;
use App\Models\SiswaUjian;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiswaSoal>
 */
class SiswaSoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_ujian_id' => SiswaUjian::factory(),
            'soal_id' => Soal::factory(),
            'urutan' => fake()->unique()->numberBetween(1, 50),
            'jawaban' => null,
        ];
    }
}
