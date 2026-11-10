<?php

use App\Models\BankSoal;
use App\Models\PesertaSoal;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin sees every soal of the bank in the peserta exam sheet, in original order', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => 'Soal pertama XYZ']);
    Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => 'Soal kedua XYZ']);
    Soal::factory()->create(['pertanyaan' => 'Soal bank lain']);

    $response = $this->get(route('admin.bank-soal.preview', $bankSoal))->assertOk();

    $response->assertSee('Mode Preview Admin')
        ->assertSee('Navigasi Soal')
        ->assertSeeInOrder(['Soal pertama XYZ', 'Soal kedua XYZ'])
        ->assertDontSee('Soal bank lain');
});

test('the preview never leaks the correct jawaban', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pilih_a' => 'Opsi Rahasia A', 'jawaban' => 'A']);

    $response = $this->get(route('admin.bank-soal.preview', $bankSoal))->assertOk();

    $response->assertSee('Opsi Rahasia A');
    expect($response->getContent())
        ->not->toContain('"jawaban":"A"')
        ->not->toContain('jawaban":"A');
});

test('opening the preview creates no attempt data', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count(3)->create(['bank_soal_id' => $bankSoal->id]);

    $this->get(route('admin.bank-soal.preview', $bankSoal))->assertOk();

    expect(PesertaSoal::count())->toBe(0);
});

test('an empty bank soal shows a friendly empty state', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    $this->get(route('admin.bank-soal.preview', $bankSoal))
        ->assertOk()
        ->assertSee('Bank soal ini belum punya soal.');
});

test('the preview requires bank-soal.manage', function () {
    actingAsAdminRole('operator');
    $bankSoal = BankSoal::factory()->create();

    $this->get(route('admin.bank-soal.preview', $bankSoal))->assertForbidden();
});

test('a peserta cannot open the admin preview', function () {
    actingAsPeserta();
    $bankSoal = BankSoal::factory()->create();

    $this->get(route('admin.bank-soal.preview', $bankSoal))->assertRedirect(route('admin.login'));
});
