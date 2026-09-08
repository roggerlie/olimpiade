<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * All permissions gating the /admin area, and which of the two
     * non-super-admin roles get them. `administrator` needs none of this —
     * it bypasses every check via the Gate::before in AppServiceProvider.
     *
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'master-data.manage',
            'bank-soal.manage',
            'ujian.manage',
            'ujian.view',
            'ujian-peserta.manage',
            'leaderboard.view',
            'peserta.manage',
            'kartu-peserta.print',
        ],
        'operator' => [
            'ujian.view',
            'ujian-peserta.manage',
            'leaderboard.view',
            'peserta.manage',
            'kartu-peserta.print',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = collect(self::ROLE_PERMISSIONS)->flatten()->unique()->values();
        $permissions->push('users.manage');

        $permissions->each(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

        // DatabaseSeeder runs under WithoutModelEvents, which silently
        // suppresses the `saved` hook Spatie's Permission model normally
        // fires to invalidate its permission cache. Without this explicit
        // clear, syncPermissions() below can't see the rows just created
        // above and throws PermissionDoesNotExist.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLE_PERMISSIONS as $role => $permissionNames) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissionNames);
        }
    }
}
