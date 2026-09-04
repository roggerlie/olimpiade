<?php

use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it lists existing jenjang', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create(['kode' => 'SD', 'nama' => 'Sekolah Dasar']);

    Livewire::test('admin.jenjang.manager')
        ->assertSee('Sekolah Dasar')
        ->assertSee('SD');
});

test('it validates required fields before creating', function () {
    actingAsAdmin();

    Livewire::test('admin.jenjang.manager')
        ->call('create')
        ->set('kode', '')
        ->set('nama', '')
        ->call('save')
        ->assertHasErrors(['kode' => 'required', 'nama' => 'required']);

    expect(Jenjang::count())->toBe(0);
});

test('it creates a new jenjang', function () {
    actingAsAdmin();

    Livewire::test('admin.jenjang.manager')
        ->call('create')
        ->set('kode', 'SD')
        ->set('nama', 'Sekolah Dasar')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(Jenjang::where('kode', 'SD')->where('nama', 'Sekolah Dasar')->exists())->toBeTrue();
});

test('it rejects a duplicate kode', function () {
    actingAsAdmin();
    Jenjang::factory()->create(['kode' => 'SD']);

    Livewire::test('admin.jenjang.manager')
        ->call('create')
        ->set('kode', 'SD')
        ->set('nama', 'Sekolah Dasar Baru')
        ->call('save')
        ->assertHasErrors(['kode']);

    expect(Jenjang::count())->toBe(1);
});

test('it prefills and updates an existing jenjang', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create(['kode' => 'SD', 'nama' => 'Sekolah Dasar']);

    Livewire::test('admin.jenjang.manager')
        ->call('edit', $jenjang->id)
        ->assertSet('kode', 'SD')
        ->assertSet('nama', 'Sekolah Dasar')
        ->set('nama', 'Sekolah Dasar Negeri')
        ->call('save')
        ->assertHasNoErrors();

    expect($jenjang->fresh()->nama)->toBe('Sekolah Dasar Negeri');
});

test('it deletes an unused jenjang', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();

    Livewire::test('admin.jenjang.manager')
        ->call('delete', $jenjang->id);

    expect(Jenjang::find($jenjang->id))->toBeNull();
});

test('it refuses to delete a jenjang still referenced by a bank soal', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    BankSoal::factory()->create(['jenjang_id' => $jenjang->id, 'pelajaran_id' => Pelajaran::factory()]);

    Livewire::test('admin.jenjang.manager')
        ->call('delete', $jenjang->id)
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'tidak bisa dihapus'));

    expect(Jenjang::find($jenjang->id))->not->toBeNull();
});
