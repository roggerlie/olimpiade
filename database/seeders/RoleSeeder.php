<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

        collect(['administrator', 'admin', 'operator'])->each(
            fn (string $role) => Role::findOrCreate($role, 'web')
        );

        // Peserta stopped being a `users` row + Spatie `peserta` role long ago
        // (they're their own `peserta` guard/table now — see the
        // move_peserta_login_to_peserta_table migration), so the role is dead
        // weight. Deleting it also clears its leftover model_has_roles rows
        // (cascadeOnDelete), which by now only point at deleted users.
        Role::where('name', 'peserta')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
