<?php

use App\Models\BankSoal;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('an operator cannot call ujian manager mutating actions', function () {
    actingAsAdminRole('operator');
    $bankSoal = BankSoal::factory()->create();
    $ujian = Ujian::factory()->create(['bank_soal_id' => $bankSoal->id]);

    Livewire::test('admin.ujian.manager')->call('create')->assertForbidden();
    Livewire::test('admin.ujian.manager')->call('edit', $ujian->id)->assertForbidden();
    Livewire::test('admin.ujian.manager')->call('save')->assertForbidden();
    Livewire::test('admin.ujian.manager')->call('delete', $ujian->id)->assertForbidden();

    expect($ujian->fresh())->not->toBeNull();
});

test('an admin can call ujian manager mutating actions', function () {
    actingAsAdminRole('admin');
    $ujian = Ujian::factory()->create();

    Livewire::test('admin.ujian.manager')->call('create')->assertOk();
    Livewire::test('admin.ujian.manager')->call('edit', $ujian->id)->assertOk();
});

test('an operator does not see ujian manage buttons, but does see the leaderboard link', function () {
    actingAsAdminRole('operator');
    Ujian::factory()->create(['nama' => 'Ujian Sains']);

    $response = Livewire::test('admin.ujian.manager');

    $response->assertDontSee('+ Tambah Ujian');
    $response->assertDontSee('wire:click="edit', false);
    $response->assertSee('Leaderboard');
});

test('an operator cannot manage jenjang, pelajaran, bank soal, or soal', function () {
    actingAsAdminRole('operator');
    $bankSoal = BankSoal::factory()->create();
    $soal = Soal::factory()->create(['bank_soal_id' => $bankSoal->id]);

    Livewire::test('admin.jenjang.manager')->call('create')->assertForbidden();
    Livewire::test('admin.pelajaran.manager')->call('create')->assertForbidden();
    Livewire::test('admin.bank-soal.manager')->call('create')->assertForbidden();
    // Tambah/Ubah Soal moved off admin.soal.manager onto its own page component
    // (admin.soal.form), which authorizes in mount() — see SoalFormTest.
    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])->assertForbidden();
    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])->call('delete', $soal->id)->assertForbidden();
});

test('an admin can manage jenjang, pelajaran, bank soal, and soal', function () {
    actingAsAdminRole('admin');
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.jenjang.manager')->call('create')->assertOk();
    Livewire::test('admin.pelajaran.manager')->call('create')->assertOk();
    Livewire::test('admin.bank-soal.manager')->call('create')->assertOk();
    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])->assertOk();
});
