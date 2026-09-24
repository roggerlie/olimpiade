<?php

use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    collect(['administrator', 'admin', 'operator'])->each(fn (string $role) => Role::findOrCreate($role, 'web'));
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

test('an administrator can log in through the admin form and reach the admin dashboard', function () {
    $administrator = User::factory()->create(['username' => 'administrator1']);
    $administrator->assignRole('administrator');

    $response = $this->post(route('admin.login'), [
        'username' => 'administrator1',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($administrator);
});

test('an operator can log in through the admin form and reach the admin dashboard', function () {
    $operator = User::factory()->create(['username' => 'operator1']);
    $operator->assignRole('operator');

    $response = $this->post(route('admin.login'), [
        'username' => 'operator1',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($operator);
});

test('a student can log in through the student form (using their noreg) and reach the cbt dashboard', function () {
    $peserta = Peserta::factory()->create(['noreg' => '1000000001', 'password' => Hash::make('password')]);

    $response = $this->post(route('login'), [
        'username' => '1000000001',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('cbt.dashboard'));
    $this->assertAuthenticatedAs($peserta, 'peserta');
});

test('a student cannot log in through the admin form', function () {
    Peserta::factory()->create(['noreg' => '1000000002', 'password' => Hash::make('password')]);

    $response = $this->post(route('admin.login'), [
        'username' => '1000000002',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest('web');
});

test('an admin cannot log in through the student form', function () {
    $admin = User::factory()->create(['username' => 'admin2']);
    $admin->assignRole('admin');

    $response = $this->post(route('login'), [
        'username' => 'admin2',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest('peserta');
});

test('wrong credentials are rejected', function () {
    Peserta::factory()->create(['noreg' => '1000000003', 'password' => Hash::make('password')]);

    $response = $this->post(route('login'), [
        'username' => '1000000003',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest('peserta');
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
    $this->assertGuest('web');
});

test('a logged in peserta can log out', function () {
    actingAsPeserta();

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest('peserta');
});

test('the student login page lists every pelajaran as a cabang lomba open to every jenjang', function () {
    Pelajaran::factory()->create(['nama' => 'Matematika']);
    Pelajaran::factory()->create(['nama' => 'Astronomi']);
    Jenjang::factory()->create(['kode' => '01', 'nama' => 'SD']);
    Jenjang::factory()->create(['kode' => '02', 'nama' => 'SLTP']);

    $this->get(route('login'))
        ->assertOk()
        ->assertSeeInOrder(['Olimpiade Matematika', 'Logika, aljabar &amp; pemecahan masalah', 'SD', 'SLTP'], false)
        ->assertSeeInOrder(['Olimpiade Astronomi', 'SD', 'SLTP']);
});
