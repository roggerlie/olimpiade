<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new PermissionSeeder)->run();
});

test('administrator can reach the roles page', function () {
    actingAsAdmin();

    $this->get(route('admin.roles.index'))->assertOk();
});

test('admin and operator are refused the roles page even by direct URL', function () {
    actingAsAdminRole('admin');
    $this->get(route('admin.roles.index'))->assertForbidden();

    actingAsAdminRole('operator');
    $this->get(route('admin.roles.index'))->assertForbidden();
});

test('the sidebar only shows Kelola Peran to an administrator', function () {
    actingAsAdmin();
    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Kelola Peran');

    actingAsAdminRole('admin');
    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Kelola Peran');
});

test('the component itself refuses a non-administrator regardless of route access', function () {
    actingAsAdminRole('admin');

    Livewire::test('admin.roles.manager')->assertForbidden();
});

test('it prefills the current permissions for admin and operator', function () {
    actingAsAdmin();

    // Keys use safeKey() form (dots swapped for "__") — see manager.php's
    // docblock on $checked for why the raw dotted permission name can't be
    // used as a wire:model leaf segment.
    Livewire::test('admin.roles.manager')
        ->assertSet('checked.admin.bank-soal__manage', true)
        ->assertSet('checked.operator.bank-soal__manage', false)
        ->assertSet('checked.operator.ujian__view', true);
});

test('saving unchecks a permission from a role', function () {
    actingAsAdmin();

    expect(Role::findByName('admin', 'web')->hasPermissionTo('bank-soal.manage'))->toBeTrue();

    Livewire::test('admin.roles.manager')
        ->set('checked.admin.bank-soal__manage', false)
        ->call('save')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, 'disimpan'));

    expect(Role::findByName('admin', 'web')->fresh()->hasPermissionTo('bank-soal.manage'))->toBeFalse();
});

test('saving grants a permission a role did not have before', function () {
    actingAsAdmin();

    expect(Role::findByName('operator', 'web')->hasPermissionTo('bank-soal.manage'))->toBeFalse();

    Livewire::test('admin.roles.manager')
        ->set('checked.operator.bank-soal__manage', true)
        ->call('save');

    expect(Role::findByName('operator', 'web')->fresh()->hasPermissionTo('bank-soal.manage'))->toBeTrue();
});

test('users.manage is never exposed as an editable checkbox', function () {
    actingAsAdmin();

    Livewire::test('admin.roles.manager')
        ->assertDontSee('checked.admin.users__manage', false)
        ->assertSet('checked.admin.users__manage', null);
});

test('saving never grants users.manage no matter what is posted', function () {
    actingAsAdmin();

    expect(Role::findByName('admin', 'web')->hasPermissionTo('users.manage'))->toBeFalse();

    // Even if something tried to smuggle it in via the bound array, save()
    // only ever syncs permissions recomputed from the fixed PERMISSION_LABELS
    // catalog — users.manage was never in it to begin with.
    Livewire::test('admin.roles.manager')
        ->set('checked.admin.users__manage', true)
        ->call('save');

    expect(Role::findByName('admin', 'web')->fresh()->hasPermissionTo('users.manage'))->toBeFalse();
});

test('a permission newly revoked from admin actually locks the gated route', function () {
    actingAsAdmin();

    Livewire::test('admin.roles.manager')
        ->set('checked.admin.master-data__manage', false)
        ->call('save');

    // Not actingAsAdminRole('admin'): that helper re-runs PermissionSeeder,
    // which would reset the very change save() just made back to its
    // hardcoded defaults before this assertion even runs.
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $this->get(route('admin.master'))->assertForbidden();
});
