<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // actingAsAdmin() only seeds `administrator`; this manager also assigns
    // `admin`/`operator`, so make sure every /admin role exists.
    (new PermissionSeeder)->run();
});

test('it lists existing admin-tier users but not roleless accounts', function () {
    $admin = actingAsAdmin();
    $operator = User::factory()->create(['name' => 'Budi Operator']);
    $operator->assignRole('operator');
    // Peserta aren't `users` rows at all anymore (see App\Models\Peserta) —
    // this just covers a stray user with no admin-tier role.
    User::factory()->create(['name' => 'Citra Tanpa Role']);

    Livewire::test('admin.users.manager')
        ->assertSee($admin->name)
        ->assertSee('Budi Operator')
        ->assertDontSee('Citra Tanpa Role');
});

test('it validates required fields before creating', function () {
    actingAsAdmin();

    Livewire::test('admin.users.manager')
        ->call('create')
        ->call('save')
        ->assertHasErrors(['nama' => 'required', 'username' => 'required', 'password' => 'required', 'role' => 'required']);

    expect(User::count())->toBe(1); // only the actingAsAdmin() actor itself
});

test('it creates a new user with the chosen role', function () {
    actingAsAdmin();

    Livewire::test('admin.users.manager')
        ->call('create')
        ->set('nama', 'Budi Operator')
        ->set('username', 'budi.operator')
        ->set('password', 'password')
        ->set('role', 'operator')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $user = User::where('username', 'budi.operator')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('operator'))->toBeTrue();
});

test('it creates a user with an optional email, for later Google login linking', function () {
    actingAsAdmin();

    Livewire::test('admin.users.manager')
        ->call('create')
        ->set('nama', 'Budi Operator')
        ->set('username', 'budi.operator')
        ->set('email', 'budi@sekolah.test')
        ->set('password', 'password')
        ->set('role', 'operator')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('username', 'budi.operator')->first()?->email)->toBe('budi@sekolah.test');
});

test('it creates a user without an email just fine', function () {
    actingAsAdmin();

    Livewire::test('admin.users.manager')
        ->call('create')
        ->set('nama', 'Budi Operator')
        ->set('username', 'budi.operator')
        ->set('password', 'password')
        ->set('role', 'operator')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('username', 'budi.operator')->first()?->email)->toBeNull();
});

test('it rejects a duplicate email', function () {
    actingAsAdmin();
    User::factory()->create(['email' => 'taken@sekolah.test']);

    Livewire::test('admin.users.manager')
        ->call('create')
        ->set('nama', 'Budi')
        ->set('username', 'budi')
        ->set('email', 'taken@sekolah.test')
        ->set('password', 'password')
        ->set('role', 'operator')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('it rejects a duplicate username', function () {
    actingAsAdmin();
    User::factory()->create(['username' => 'taken']);

    Livewire::test('admin.users.manager')
        ->call('create')
        ->set('nama', 'Budi')
        ->set('username', 'taken')
        ->set('password', 'password')
        ->set('role', 'operator')
        ->call('save')
        ->assertHasErrors(['username']);
});

test('it prefills and updates an existing user, changing their role', function () {
    actingAsAdmin();
    $user = User::factory()->create(['name' => 'Budi', 'username' => 'budi']);
    $user->assignRole('operator');

    Livewire::test('admin.users.manager')
        ->call('edit', $user->id)
        ->assertSet('nama', 'Budi')
        ->assertSet('username', 'budi')
        ->assertSet('role', 'operator')
        ->set('nama', 'Budi Santoso')
        ->set('role', 'admin')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Budi Santoso')
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('operator'))->toBeFalse();
});

test('editing a user without a new password keeps their existing password', function () {
    actingAsAdmin();
    $user = User::factory()->create();
    $user->assignRole('operator');
    $originalHash = $user->password;

    Livewire::test('admin.users.manager')
        ->call('edit', $user->id)
        ->set('password', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->password)->toBe($originalHash);
});

test('it deletes a non-administrator user', function () {
    actingAsAdmin();
    $user = User::factory()->create();
    $user->assignRole('operator');

    Livewire::test('admin.users.manager')->call('delete', $user->id);

    expect(User::find($user->id))->toBeNull();
});

test('an administrator cannot delete their own account', function () {
    $admin = actingAsAdmin();

    Livewire::test('admin.users.manager')
        ->call('delete', $admin->id)
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'akun sendiri'));

    expect(User::find($admin->id))->not->toBeNull();
});

test('the last administrator role cannot be downgraded', function () {
    $admin = actingAsAdmin();

    Livewire::test('admin.users.manager')
        ->call('edit', $admin->id)
        ->set('role', 'admin')
        ->call('save')
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'Administrator terakhir'));

    expect($admin->fresh()->hasRole('administrator'))->toBeTrue();
});

test('a second administrator can be downgraded when another administrator remains', function () {
    actingAsAdmin();
    $secondAdmin = User::factory()->create();
    $secondAdmin->assignRole('administrator');

    Livewire::test('admin.users.manager')
        ->call('edit', $secondAdmin->id)
        ->set('role', 'admin')
        ->call('save')
        ->assertHasNoErrors();

    expect($secondAdmin->fresh()->hasRole('admin'))->toBeTrue();
});
