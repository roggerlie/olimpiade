<?php

use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it returns no results for a query shorter than 2 characters', function () {
    actingAsAdmin();
    Peserta::factory()->create(['nama' => 'Budi Santoso']);

    $component = Livewire::test('admin.search-bar')->set('query', 'b');

    expect($component->instance()->pesertaResults())->toBeEmpty()
        ->and($component->instance()->ujianResults())->toBeEmpty();
});

test('it finds peserta by nama or noreg', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    Peserta::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Budi Santoso', 'noreg' => '1000099']);
    Peserta::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Citra Lestari', 'noreg' => '1000100']);

    $byName = Livewire::test('admin.search-bar')->set('query', 'Budi')->instance()->pesertaResults();
    expect($byName->pluck('nama')->all())->toBe(['Budi Santoso']);

    $byNoreg = Livewire::test('admin.search-bar')->set('query', '1000100')->instance()->pesertaResults();
    expect($byNoreg->pluck('nama')->all())->toBe(['Citra Lestari']);
});

test('it finds ujian by nama', function () {
    actingAsAdmin();
    Ujian::factory()->create(['nama' => 'Ujian Matematika SD']);
    Ujian::factory()->create(['nama' => 'Ujian Bahasa Inggris SMP']);

    $result = Livewire::test('admin.search-bar')->set('query', 'Matematika')->instance()->ujianResults();

    expect($result->pluck('nama')->all())->toBe(['Ujian Matematika SD']);
});

test('a peserta result deep-links into the peserta manager pre-filtered', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    Peserta::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Budi Santoso']);

    Livewire::test('admin.search-bar')
        ->set('query', 'Budi')
        ->assertSeeHtml(route('admin.peserta.index', ['q' => 'Budi Santoso']));
});
