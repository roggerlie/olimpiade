<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // The old single full-access role was named `admin`. Rename it to
        // `administrator` before recreating the roles below, so any user
        // already holding it keeps full access under the new name instead
        // of losing it — and so `admin` is free to become the mid tier.
        // Guarded by a not-exists check so re-seeding after the rename has
        // already happened doesn't clobber the (by then legitimate) `admin`
        // mid-tier role.
        if (! Role::where('name', 'administrator')->where('guard_name', 'web')->exists()) {
            Role::where('name', 'admin')->where('guard_name', 'web')->update(['name' => 'administrator']);
        }

        collect(['administrator', 'admin', 'operator', 'peserta'])->each(
            fn (string $role) => Role::findOrCreate($role, 'web')
        );
    }
}
