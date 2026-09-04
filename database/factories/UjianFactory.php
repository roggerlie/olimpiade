<?php

namespace Database\Factories;

use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Ujian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ujian>
 */
class UjianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sesiMulai = fake()->dateTimeBetween('now', '+1 week');
        $sesiSelesai = (clone $sesiMulai)->modify('+2 hours');

        return [
            'bank_soal_id' => BankSoal::factory(),
            'jenjang_id' => Jenjang::factory(),
            'pelajaran_id' => Pelajaran::factory(),
            'nama' => fake()->words(3, true),
            'jumlah_soal' => 50,
            'durasi_detik' => 90 * 60,
            'sesi_mulai' => $sesiMulai,
            'sesi_selesai' => $sesiSelesai,
            'deskripsi' => fake()->optional()->sentence(),
        ];
    }
}
