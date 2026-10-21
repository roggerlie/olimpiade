<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * `log-aktivitas.view` gates Sistem → Log Aktivitas. Created here (not only
 * in PermissionSeeder) so an existing install gets it on `migrate` alone —
 * Kelola Peran's syncPermissions() throws on a permission that doesn't exist.
 * Granted to no role by default: administrator already passes every check
 * (Gate::before), and handing it to admin/operator is a Kelola Peran choice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::findOrCreate('log-aktivitas.view', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', 'log-aktivitas.view')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
