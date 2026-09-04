<?php

namespace Database\Factories;

use App\Models\Siswa;
use App\Models\SiswaUjian;
use App\Models\Ujian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiswaUjian>
 */
class SiswaUjianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'ujian_id' => Ujian::factory(),
            'waktu_mulai' => null,
            'waktu_selesai' => null,
            'benar' => null,
            'salah' => null,
            'nilai' => null,
        ];
    }

    public function selesai(): static
    {
        return $this->state(function (array $attributes) {
            $benar = fake()->numberBetween(10, 50);
            $salah = fake()->numberBetween(0, 50 - $benar);

            return [
                'waktu_mulai' => now()->subHour(),
                'waktu_selesai' => now(),
                'benar' => $benar,
                'salah' => $salah,
                'nilai' => max(0, $benar * 9 - $salah),
            ];
        });
    }
}
