<?php

use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it lists existing peserta with their jenjang', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create(['nama' => 'Sekolah Dasar']);
    Peserta::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Budi', 'noreg' => '1000001']);

    Livewire::test('admin.peserta.manager')
        ->assertSee('Budi')
        ->assertSee('1000001')
        ->assertSee('Sekolah Dasar');
});

test('it validates required fields before creating', function () {
    actingAsAdmin();

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->call('save')
        ->assertHasErrors(['noreg' => 'required', 'nama' => 'required', 'jenjangId' => 'required', 'asalSekolah' => 'required', 'password' => 'required']);

    expect(Peserta::count())->toBe(0);
});

test('it creates a peserta with a matching login account', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->set('noreg', '1000001')
        ->set('nama', 'Budi Santoso')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Contoh')
        ->set('password', 'rahasia')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $peserta = Peserta::where('noreg', '1000001')->first();
    expect($peserta)->not->toBeNull();

    $user = User::where('username', '1000001')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('peserta'))->toBeTrue()
        ->and($peserta->user_id)->toBe($user->id)
        ->and(Hash::check('rahasia', $user->password))->toBeTrue();
});

test('it rejects a duplicate noreg', function () {
    actingAsAdmin();
    Peserta::factory()->create(['noreg' => '1000001']);
    $jenjang = Jenjang::factory()->create();

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->set('noreg', '1000001')
        ->set('nama', 'Lain')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Lain')
        ->set('password', 'rahasia')
        ->call('save')
        ->assertHasErrors(['noreg']);

    expect(Peserta::count())->toBe(1);
});

test('it prefills and updates an existing peserta without requiring a new password', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['nama' => 'Lama']);

    Livewire::test('admin.peserta.manager')
        ->call('edit', $peserta->id)
        ->assertSet('nama', 'Lama')
        ->assertSet('password', '')
        ->set('nama', 'Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($peserta->fresh()->nama)->toBe('Baru');
});

test('it updates the login username when noreg changes', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['noreg' => '1000001']);

    Livewire::test('admin.peserta.manager')
        ->call('edit', $peserta->id)
        ->set('noreg', '2000002')
        ->call('save')
        ->assertHasNoErrors();

    expect($peserta->fresh()->noreg)->toBe('2000002')
        ->and($peserta->user->fresh()->username)->toBe('2000002');
});

test('deleting a peserta also removes their login account', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create();
    $userId = $peserta->user_id;

    Livewire::test('admin.peserta.manager')->call('delete', $peserta->id);

    expect(Peserta::find($peserta->id))->toBeNull()
        ->and(User::find($userId))->toBeNull();
});
