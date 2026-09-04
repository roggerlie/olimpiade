<?php

use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function baseSoalPayload(): array
{
    return [
        'pertanyaan' => 'Berapa 2 + 2?',
        'pilihA' => '3',
        'pilihB' => '4',
        'pilihC' => '5',
        'pilihD' => '6',
        'jawaban' => 'B',
    ];
}

test('it lists soal belonging to the given bank soal only', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    $other = BankSoal::factory()->create();
    Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => 'Soal milik bank ini']);
    Soal::factory()->create(['bank_soal_id' => $other->id, 'pertanyaan' => 'Soal bank lain']);

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->assertSee('Soal milik bank ini')
        ->assertDontSee('Soal bank lain');
});

test('it validates required fields before creating', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->call('create')
        ->call('save')
        ->assertHasErrors(['pertanyaan', 'pilihA', 'pilihB', 'pilihC', 'pilihD', 'jawaban']);

    expect(Soal::count())->toBe(0);
});

test('it creates a new soal scoped to the bank soal', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->call('create')
        ->set(baseSoalPayload())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal)->not->toBeNull()
        ->and($soal->jawaban)->toBe('B');
});

test('it rejects jawaban E when pilihan E is empty', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->call('create')
        ->set(array_merge(baseSoalPayload(), ['jawaban' => 'E']))
        ->call('save')
        ->assertHasErrors(['jawaban']);

    expect(Soal::count())->toBe(0);
});

test('it accepts jawaban E when pilihan E is filled', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->call('create')
        ->set(array_merge(baseSoalPayload(), ['pilihE' => '7', 'jawaban' => 'E']))
        ->call('save')
        ->assertHasNoErrors();

    expect(Soal::where('bank_soal_id', $bankSoal->id)->where('jawaban', 'E')->exists())->toBeTrue();
});

test('it prefills and updates an existing soal', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    $soal = Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => 'Lama']);

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->call('edit', $soal->id)
        ->assertSet('pertanyaan', 'Lama')
        ->set('pertanyaan', 'Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($soal->fresh()->pertanyaan)->toBe('Baru');
});

test('tandaiJawaban marks the clicked pilihan as the answer', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->call('create')
        ->assertSet('jawaban', '')
        ->call('tandaiJawaban', 'C')
        ->assertSet('jawaban', 'C')
        ->call('tandaiJawaban', 'A')
        ->assertSet('jawaban', 'A');
});

test('a soal created via tandaiJawaban persists the right jawaban', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])
        ->call('create')
        ->set([
            'pertanyaan' => 'Berapa 2 + 2?',
            'pilihA' => '3',
            'pilihB' => '4',
            'pilihC' => '5',
            'pilihD' => '6',
        ])
        ->call('tandaiJawaban', 'B')
        ->call('save')
        ->assertHasNoErrors();

    expect(Soal::where('bank_soal_id', $bankSoal->id)->first()->jawaban)->toBe('B');
});

test('it deletes a soal', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    $soal = Soal::factory()->create(['bank_soal_id' => $bankSoal->id]);

    Livewire::test('admin.soal.manager', ['bankSoalId' => $bankSoal->id])->call('delete', $soal->id);

    expect(Soal::find($soal->id))->toBeNull();
});
