<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('it seeds exactly the three admin-tier roles', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::orderBy('id')->pluck('name')->all())->toBe(['administrator', 'admin', 'operator']);
});

test('it removes the obsolete peserta role left over from older databases', function () {
    Role::findOrCreate('peserta', 'web');

    $this->seed(RoleSeeder::class);
    $this->seed(RoleSeeder::class);

    expect(Role::where('name', 'peserta')->exists())->toBeFalse()
        ->and(Role::count())->toBe(3);
});
