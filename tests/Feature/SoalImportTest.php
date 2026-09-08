<?php

use App\Imports\SoalImport;
use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Unit-style: exercises App\Imports\SoalImport::collection() directly with a
// plain Collection of rows, sidestepping actual spreadsheet I/O — see
// tests/Feature/Admin/SoalImportTest.php for the Livewire upload plumbing.

test('it imports valid rows scoped to the given bank soal', function () {
    $bankSoal = BankSoal::factory()->create();

    $importer = new SoalImport($bankSoal->id);
    $importer->collection(collect([
        ['pertanyaan' => 'Berapa 2 + 2?', 'pilihan_a' => '3', 'pilihan_b' => '4', 'pilihan_c' => '5', 'pilihan_d' => '6', 'pilihan_e' => '', 'jawaban' => 'B'],
        ['pertanyaan' => 'Berapa 5 x 5?', 'pilihan_a' => '20', 'pilihan_b' => '25', 'pilihan_c' => '30', 'pilihan_d' => '35', 'pilihan_e' => '40', 'jawaban' => 'E'],
    ]));

    expect($importer->imported)->toBe(2)
        ->and($importer->errors)->toBeEmpty()
        ->and(Soal::where('bank_soal_id', $bankSoal->id)->count())->toBe(2);

    // Every field is HTML-escaped/wrapped on save (see SoalImport's own
    // docblock), so assert by substring rather than an exact string match.
    $soal = Soal::where('bank_soal_id', $bankSoal->id)->where('jawaban', 'B')->first();
    expect($soal->pertanyaan)->toContain('Berapa 2 + 2?')
        ->and($soal->pilih_b)->toContain('4')
        ->and($soal->jawaban)->toBe('B')
        ->and($soal->pilih_e)->toBeNull();
});

test('it does not import soal into a different bank soal', function () {
    $bankSoal = BankSoal::factory()->create();
    $other = BankSoal::factory()->create();

    (new SoalImport($bankSoal->id))->collection(collect([
        ['pertanyaan' => 'Q', 'pilihan_a' => 'A', 'pilihan_b' => 'B', 'pilihan_c' => 'C', 'pilihan_d' => 'D', 'jawaban' => 'A'],
    ]));

    expect(Soal::where('bank_soal_id', $bankSoal->id)->count())->toBe(1)
        ->and(Soal::where('bank_soal_id', $other->id)->count())->toBe(0);
});

test('it reports invalid rows without aborting the whole import', function () {
    $bankSoal = BankSoal::factory()->create();

    $importer = new SoalImport($bankSoal->id);
    $importer->collection(collect([
        ['pertanyaan' => 'Baris valid', 'pilihan_a' => 'A', 'pilihan_b' => 'B', 'pilihan_c' => 'C', 'pilihan_d' => 'D', 'jawaban' => 'A'],
        ['pertanyaan' => '', 'pilihan_a' => 'A', 'pilihan_b' => 'B', 'pilihan_c' => 'C', 'pilihan_d' => 'D', 'jawaban' => 'A'],
        ['pertanyaan' => 'Jawaban salah', 'pilihan_a' => 'A', 'pilihan_b' => 'B', 'pilihan_c' => 'C', 'pilihan_d' => 'D', 'jawaban' => 'Z'],
    ]));

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toHaveCount(2)
        ->and(Soal::count())->toBe(1);
});

test('it escapes a literal < or > in a cell so it renders as text, not markup', function () {
    $bankSoal = BankSoal::factory()->create();

    $importer = new SoalImport($bankSoal->id);
    $importer->collection(collect([
        ['pertanyaan' => 'Jika a < b, manakah yang benar?', 'pilihan_a' => 'a < b', 'pilihan_b' => 'a > b', 'pilihan_c' => 'a = b', 'pilihan_d' => 'Tom & Jerry', 'jawaban' => 'A'],
    ]));

    expect($importer->imported)->toBe(1);

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal->pertanyaan)->toContain('a &lt; b')
        ->and($soal->pilih_a)->toContain('a &lt; b')
        ->and($soal->pilih_b)->toContain('a &gt; b')
        ->and($soal->pilih_d)->toContain('Tom &amp; Jerry');
});

test('it imports a row containing mathematical notation and symbols intact', function () {
    $bankSoal = BankSoal::factory()->create();

    // SoalImport doesn't parse LaTeX/MathML — a spreadsheet cell is plain
    // text, so "math formula" here means the actual Unicode math symbols a
    // teacher would type directly into Excel (², √, ½, π, ×, −), not a
    // rendered equation image (that's the Word import path — see
    // tests/Feature/SoalWordImportTest.php). This just needs to survive
    // e()-escaping + the 'soal' HTMLPurifier preset without being mangled.
    $importer = new SoalImport($bankSoal->id);
    $importer->collection(collect([
        [
            'pertanyaan' => 'Berapa hasil dari 3² + √16 − ½?',
            'pilihan_a' => '12½',
            'pilihan_b' => '13',
            'pilihan_c' => '12¾',
            'pilihan_d' => '11½',
            'pilihan_e' => 'π (pi), bukan angka',
            'jawaban' => 'B',
        ],
    ]));

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toBeEmpty();

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal->pertanyaan)->toContain('3² + √16 − ½')
        ->and($soal->pilih_a)->toContain('12½')
        ->and($soal->pilih_c)->toContain('12¾')
        ->and($soal->pilih_e)->toContain('π (pi)');
});

test('it rejects jawaban E when pilihan_e is blank', function () {
    $bankSoal = BankSoal::factory()->create();

    $importer = new SoalImport($bankSoal->id);
    $importer->collection(collect([
        ['pertanyaan' => 'Q', 'pilihan_a' => 'A', 'pilihan_b' => 'B', 'pilihan_c' => 'C', 'pilihan_d' => 'D', 'pilihan_e' => '', 'jawaban' => 'E'],
    ]));

    expect($importer->imported)->toBe(0)
        ->and($importer->errors)->toHaveCount(1)
        ->and(Soal::count())->toBe(0);
});
