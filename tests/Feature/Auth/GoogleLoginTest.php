<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // PermissionSeeder only covers admin/operator (see its own docblock);
    // administrator needs to exist too since tests below assign it.
    (new PermissionSeeder)->run();
    Role::findOrCreate('administrator', 'web');
});

test('an existing admin-tier user with a matching email is logged in', function () {
    $admin = User::factory()->create(['email' => 'budi@sekolah.test']);
    $admin->assignRole('administrator');

    Socialite::fake('google', SocialiteUser::fake(['email' => 'budi@sekolah.test']));

    $this->get(route('admin.login.google.callback'))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
});

test('the email match is case-insensitive', function () {
    $admin = User::factory()->create(['email' => 'Budi@Sekolah.test']);
    $admin->assignRole('operator');

    Socialite::fake('google', SocialiteUser::fake(['email' => 'budi@sekolah.test']));

    $this->get(route('admin.login.google.callback'))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
});

test('an unrecognized Google email is rejected without creating an account', function () {
    Socialite::fake('google', SocialiteUser::fake(['email' => 'orang-asing@gmail.test']));

    $this->get(route('admin.login.google.callback'))
        ->assertRedirect(route('admin.login'))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('a matching email on a user with no admin-tier role is rejected', function () {
    // Peserta don't live in `users` at all anymore (see App\Models\Peserta),
    // so this now covers any stray/roleless `users` row rather than a
    // peserta account specifically — the `hasAnyRole` guard in
    // GoogleAuthController still needs to hold either way.
    User::factory()->create(['email' => 'siswa@sekolah.test']);

    Socialite::fake('google', SocialiteUser::fake(['email' => 'siswa@sekolah.test']));

    $this->get(route('admin.login.google.callback'))
        ->assertRedirect(route('admin.login'))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('a cancelled or failed Google handshake redirects back with an error instead of erroring out', function () {
    Socialite::fake('google', function () {
        throw new InvalidStateException;
    });

    $this->get(route('admin.login.google.callback'))
        ->assertRedirect(route('admin.login'))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});
