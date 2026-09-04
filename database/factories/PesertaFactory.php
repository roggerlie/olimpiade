<?php

namespace Database\Factories;

use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

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
        $noreg = fake()->unique()->numerify('#######');

        return [
            // Kept in sync with the paired User's username, mirroring the
            // real invariant enforced by Admin\Peserta\Manager / PesertaImport:
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
        return $this->afterCreating(function (Peserta $peserta) {
            // Runs after both rows exist, so this sees the final `noreg`
            // even when a test overrides it via ->create(['noreg' => ...]) —
            // the sync in definition() alone only covers the unoverridden case.
            $peserta->user->forceFill(['username' => $peserta->noreg])->save();

            Role::findOrCreate('peserta', 'web');
            $peserta->user->assignRole('peserta');
        });
    }
}
