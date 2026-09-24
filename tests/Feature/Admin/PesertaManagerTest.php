<?php

use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it lists existing peserta with their jenjang', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create(['nama' => 'Sekolah Dasar']);
    Peserta::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Budi', 'noreg' => '1000000001']);

    Livewire::test('admin.peserta.manager')
        ->assertSee('Budi')
        ->assertSee('1000000001')
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

test('it creates a peserta with a working login', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->set('noreg', '1000000001')
        ->set('nama', 'Budi Santoso')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Contoh')
        ->set('password', 'rahasia')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $peserta = Peserta::where('noreg', '1000000001')->first();
    expect($peserta)->not->toBeNull()
        ->and(Hash::check('rahasia', $peserta->password))->toBeTrue();
});

test('it records minat lomba (checked pelajaran) when creating a peserta', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $matematika = Pelajaran::factory()->create(['nama' => 'Matematika']);
    $sains = Pelajaran::factory()->create(['nama' => 'Sains']);

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->set('noreg', '1000000001')
        ->set('nama', 'Budi Santoso')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Contoh')
        ->set('password', 'rahasia')
        ->set('pelajaranLombaIds', [$matematika->id])
        ->call('save')
        ->assertHasNoErrors();

    $peserta = Peserta::where('noreg', '1000000001')->first();
    expect($peserta->pelajaranLomba->pluck('id')->all())->toBe([$matematika->id])
        ->and($peserta->pelajaranLomba->pluck('id'))->not->toContain($sains->id);
});

test('editing a peserta prefills their minat lomba and syncs changes (including unchecking)', function () {
    actingAsAdmin();
    $matematika = Pelajaran::factory()->create(['nama' => 'Matematika']);
    $sains = Pelajaran::factory()->create(['nama' => 'Sains']);
    $peserta = Peserta::factory()->create();
    $peserta->pelajaranLomba()->attach([$matematika->id, $sains->id]);

    Livewire::test('admin.peserta.manager')
        ->call('edit', $peserta->id)
        ->assertSet('pelajaranLombaIds', fn ($ids) => in_array($matematika->id, $ids) && in_array($sains->id, $ids))
        ->set('pelajaranLombaIds', [$sains->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($peserta->fresh()->pelajaranLomba->pluck('id')->all())->toBe([$sains->id]);
});

test('it rejects a duplicate noreg', function () {
    actingAsAdmin();
    Peserta::factory()->create(['noreg' => '1000000001']);
    $jenjang = Jenjang::factory()->create();

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->set('noreg', '1000000001')
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

test('it updates the noreg used to log in', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['noreg' => '1000000001']);

    Livewire::test('admin.peserta.manager')
        ->call('edit', $peserta->id)
        ->set('noreg', '2000000002')
        ->call('save')
        ->assertHasNoErrors();

    expect($peserta->fresh()->noreg)->toBe('2000000002');
});

test('resetting a peserta password sets it back to their noreg', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['noreg' => '1000000001', 'password' => Hash::make('rahasia-lama')]);

    Livewire::test('admin.peserta.manager')
        ->call('resetPassword', $peserta->id)
        ->assertSet('errorMessage', null);

    expect(Hash::check('1000000001', $peserta->fresh()->password))->toBeTrue();
});

test('deleting a peserta removes their login account', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create();

    Livewire::test('admin.peserta.manager')->call('delete', $peserta->id);

    expect(Peserta::find($peserta->id))->toBeNull();
});

test('the list paginates with the app-styled pagination view', function () {
    actingAsAdmin();
    Peserta::factory()->count(11)->create();

    // 10 per page (see manager.php's ->paginate(10)), so an 11th row pushes
    // a "page 2" link through resources/views/vendor/pagination/tailwind.blade.php
    // (our styled override of Laravel's default pagination view).
    Livewire::test('admin.peserta.manager')
        ->assertSeeHtml('aria-current="page"')
        ->assertSeeHtml('aria-label="Go to page 2"');
});
