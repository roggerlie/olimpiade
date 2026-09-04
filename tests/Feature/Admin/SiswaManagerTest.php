<?php

use App\Models\Jenjang;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it lists existing peserta with their jenjang', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create(['nama' => 'Sekolah Dasar']);
    Siswa::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Budi', 'noreg' => '1000001']);

    Livewire::test('admin.siswa.manager')
        ->assertSee('Budi')
        ->assertSee('1000001')
        ->assertSee('Sekolah Dasar');
});

test('it validates required fields before creating', function () {
    actingAsAdmin();

    Livewire::test('admin.siswa.manager')
        ->call('create')
        ->call('save')
        ->assertHasErrors(['noreg' => 'required', 'nama' => 'required', 'jenjangId' => 'required', 'asalSekolah' => 'required', 'password' => 'required']);

    expect(Siswa::count())->toBe(0);
});

test('it creates a peserta with a matching login account', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();

    Livewire::test('admin.siswa.manager')
        ->call('create')
        ->set('noreg', '1000001')
        ->set('nama', 'Budi Santoso')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Contoh')
        ->set('password', 'rahasia')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $siswa = Siswa::where('noreg', '1000001')->first();
    expect($siswa)->not->toBeNull();

    $user = User::where('username', '1000001')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('siswa'))->toBeTrue()
        ->and($siswa->user_id)->toBe($user->id)
        ->and(Hash::check('rahasia', $user->password))->toBeTrue();
});

test('it rejects a duplicate noreg', function () {
    actingAsAdmin();
    Siswa::factory()->create(['noreg' => '1000001']);
    $jenjang = Jenjang::factory()->create();

    Livewire::test('admin.siswa.manager')
        ->call('create')
        ->set('noreg', '1000001')
        ->set('nama', 'Lain')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Lain')
        ->set('password', 'rahasia')
        ->call('save')
        ->assertHasErrors(['noreg']);

    expect(Siswa::count())->toBe(1);
});

test('it prefills and updates an existing peserta without requiring a new password', function () {
    actingAsAdmin();
    $siswa = Siswa::factory()->create(['nama' => 'Lama']);

    Livewire::test('admin.siswa.manager')
        ->call('edit', $siswa->id)
        ->assertSet('nama', 'Lama')
        ->assertSet('password', '')
        ->set('nama', 'Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($siswa->fresh()->nama)->toBe('Baru');
});

test('it updates the login username when noreg changes', function () {
    actingAsAdmin();
    $siswa = Siswa::factory()->create(['noreg' => '1000001']);

    Livewire::test('admin.siswa.manager')
        ->call('edit', $siswa->id)
        ->set('noreg', '2000002')
        ->call('save')
        ->assertHasNoErrors();

    expect($siswa->fresh()->noreg)->toBe('2000002')
        ->and($siswa->user->fresh()->username)->toBe('2000002');
});

test('deleting a peserta also removes their login account', function () {
    actingAsAdmin();
    $siswa = Siswa::factory()->create();
    $userId = $siswa->user_id;

    Livewire::test('admin.siswa.manager')->call('delete', $siswa->id);

    expect(Siswa::find($siswa->id))->toBeNull()
        ->and(User::find($userId))->toBeNull();
});
