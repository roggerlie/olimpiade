<?php

namespace Database\Factories;

use App\Models\Jenjang;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<Siswa>
 */
class SiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $noreg = fake()->unique()->numerify('#######');

        return [
            // Kept in sync with the paired User's username, mirroring the
            // real invariant enforced by Admin\Siswa\Manager / SiswaImport:
            // a peserta logs in with their noreg.
            'user_id' => User::factory()->state(['username' => $noreg]),
            'jenjang_id' => Jenjang::factory(),
            'noreg' => $noreg,
            'nama' => fake()->name(),
            'asal_sekolah' => fake()->company().' School',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Siswa $siswa) {
            // Runs after both rows exist, so this sees the final `noreg`
            // even when a test overrides it via ->create(['noreg' => ...]) —
            // the sync in definition() alone only covers the unoverridden case.
            $siswa->user->forceFill(['username' => $siswa->noreg])->save();

            Role::findOrCreate('siswa', 'web');
            $siswa->user->assignRole('siswa');
        });
    }
}
