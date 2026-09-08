<?php

use App\Imports\SoalWordImport;
use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;

uses(RefreshDatabase::class);

// Builds a real .docx fixture with PhpWord (matching the shape
// App\Exports\SoalWordTemplateExport produces) rather than committing a
// binary file — SoalWordImport reads the raw XML directly (see its own
// docblock for why), so this exercises that exact code path end to end,
// image included.

/**
 * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string}>  $baris
 */
function buatDocxSoal(array $baris, ?string $gambarPathUntukSelPertama = null): string
{
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $table = $section->addTable();

    $table->addRow();
    foreach (['Pertanyaan', 'A', 'B', 'C', 'D', 'E', 'Jawaban'] as $judul) {
        $table->addCell(1500)->addText($judul);
    }

    foreach ($baris as $i => $kolom) {
        $table->addRow();
        foreach ($kolom as $j => $isi) {
            $cell = $table->addCell(1500);

            if ($isi !== '') {
                $cell->addText($isi);
            }

            // Drop the fixture image into the very first row's "A" cell (index 1).
            if ($i === 0 && $j === 1 && $gambarPathUntukSelPertama) {
                $cell->addImage($gambarPathUntukSelPertama, ['width' => 40, 'height' => 40]);
            }
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'soal-word-test').'.docx';
    $phpWord->save($path, 'Word2007');

    return $path;
}

function gambarFixturePng(): string
{
    $path = tempnam(sys_get_temp_dir(), 'soal-word-fixture').'.png';
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

    return $path;
}

test('it imports a valid row, gambar included', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxSoal([
        ['Berapa hasil dari 2 + 2?', '', '4', '5', '6', '', 'B'],
    ], gambarFixturePng());

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toBeEmpty();

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal->pertanyaan)->toContain('Berapa hasil dari 2 + 2?')
        // Pilihan A had no text in the fixture, only the image — its
        // extracted gambar is now embedded inline as an <img> rather than
        // living in a separate pilih_a_gambar column.
        ->and($soal->pilih_a)->toContain('<img')
        ->and($soal->pilih_b)->toContain('4')
        ->and($soal->jawaban)->toBe('B');

    preg_match('/src="([^"]+)"/', $soal->pilih_a, $match);
    $path = 'soal/'.$bankSoal->id.'/'.basename(parse_url($match[1], PHP_URL_PATH));
    Storage::disk('public')->assertExists($path);
});

test('it imports a row combining a formula image with mathematical notation in the surrounding text', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();

    // SoalWordImport doesn't understand Word's native equation objects
    // (OMML <m:oMath>) — the realistic way a formula reaches this importer
    // is exactly like any other picture: an admin renders/screenshots the
    // equation and drops it into the cell as an image (the fixture PNG
    // stands in for that here), typically alongside plain math notation in
    // the surrounding cell text.
    $docx = buatDocxSoal([
        ['Perhatikan gambar rumus a² + b² = c² di bawah. Jika a = 3 dan b = 4, berapa c?', '', '7', '25', '12', '', 'A'],
    ], gambarFixturePng());

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toBeEmpty();

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal->pertanyaan)->toContain('a² + b² = c²')
        ->and($soal->pilih_a)->toContain('<img')
        ->and($soal->jawaban)->toBe('A');

    preg_match('/src="([^"]+)"/', $soal->pilih_a, $match);
    $path = 'soal/'.$bankSoal->id.'/'.basename(parse_url($match[1], PHP_URL_PATH));
    Storage::disk('public')->assertExists($path);
});

test('it reports a row missing pertanyaan text without aborting the rest', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxSoal([
        ['', 'A', 'B', 'C', 'D', '', 'A'],
        ['Baris valid', 'A', 'B', 'C', 'D', '', 'A'],
    ]);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toHaveCount(1)
        ->and(Soal::count())->toBe(1);
});

test('it rejects a pilihan with neither text nor gambar', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxSoal([
        ['Pertanyaan', '', 'B', 'C', 'D', '', 'A'],
    ]);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(0)
        ->and($importer->errors)->toHaveCount(1)
        ->and(Soal::count())->toBe(0);
});

test('it rejects jawaban E when pilihan E has neither text nor gambar', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxSoal([
        ['Pertanyaan', 'A', 'B', 'C', 'D', '', 'E'],
    ]);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(0)
        ->and($importer->errors)->toHaveCount(1)
        ->and(Soal::count())->toBe(0);
});

test('it rejects an invalid jawaban letter', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxSoal([
        ['Pertanyaan', 'A', 'B', 'C', 'D', '', 'Z'],
    ]);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(0)
        ->and($importer->errors)->toHaveCount(1)
        ->and(Soal::count())->toBe(0);
});

test('it reports a file with no table at all', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();

    $phpWord = new PhpWord;
    $phpWord->addSection()->addText('Bukan tabel sama sekali.');
    $path = tempnam(sys_get_temp_dir(), 'soal-word-no-table').'.docx';
    $phpWord->save($path, 'Word2007');

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($path);

    expect($importer->imported)->toBe(0)
        ->and($importer->errors)->toHaveCount(1);
});

test('it does not import soal into a different bank soal', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $other = BankSoal::factory()->create();
    $docx = buatDocxSoal([
        ['Pertanyaan', 'A', 'B', 'C', 'D', '', 'A'],
    ]);

    (new SoalWordImport($bankSoal->id))->import($docx);

    expect(Soal::where('bank_soal_id', $bankSoal->id)->count())->toBe(1)
        ->and(Soal::where('bank_soal_id', $other->id)->count())->toBe(0);
});
