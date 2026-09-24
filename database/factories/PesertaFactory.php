<?php

namespace Database\Factories;

use App\Models\Jenjang;
use App\Models\Peserta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Peserta>
 */
class PesertaFactory extends Factory
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
            // 10 digits, matching a real NISN.
            'noreg' => fake()->unique()->numerify('##########'),
            'nama' => fake()->name(),
            'asal_sekolah' => fake()->company().' School',
            'password' => static::$password ??= Hash::make('password'),
            'password_plain' => 'password',
        ];
    }

    /**
     * The current password being used by the factory, cached the same way
     * Laravel's own UserFactory does it — Hash::make() is expensive enough
     * that re-running it per row noticeably slows down large test suites.
     */
    protected static ?string $password = null;
}
