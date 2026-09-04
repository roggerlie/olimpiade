<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    collect(['admin', 'siswa'])->each(fn (string $role) => Role::findOrCreate($role, 'web'));
});

test('an admin can log in through the admin form and reach the admin dashboard', function () {
    $admin = User::factory()->create(['username' => 'admin1']);
    $admin->assignRole('admin');

    $response = $this->post(route('admin.login'), [
        'username' => 'admin1',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
});

test('a student can log in through the student form and reach the cbt dashboard', function () {
    $siswa = User::factory()->create(['username' => 'siswa1']);
    $siswa->assignRole('siswa');

    $response = $this->post(route('login'), [
        'username' => 'siswa1',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('cbt.dashboard'));
    $this->assertAuthenticatedAs($siswa);
});

test('a student cannot log in through the admin form', function () {
    $siswa = User::factory()->create(['username' => 'siswa2']);
    $siswa->assignRole('siswa');

    $response = $this->post(route('admin.login'), [
        'username' => 'siswa2',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('an admin cannot log in through the student form', function () {
    $admin = User::factory()->create(['username' => 'admin2']);
    $admin->assignRole('admin');

    $response = $this->post(route('login'), [
        'username' => 'admin2',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('wrong credentials are rejected', function () {
    User::factory()->create(['username' => 'someone'])->assignRole('siswa');

    $response = $this->post(route('login'), [
        'username' => 'someone',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('a guest visiting an admin route is redirected to the admin login', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

test('a guest visiting a student route is redirected to the student login', function () {
    $this->get(route('cbt.dashboard'))->assertRedirect(route('login'));
});

test('a logged in admin can log out', function () {
    $admin = User::factory()->create(['username' => 'admin3']);
    $admin->assignRole('admin');

    $this->actingAs($admin)->post(route('logout'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
});
