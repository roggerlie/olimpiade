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

    // A formula can also arrive as a plain picture (a screenshot of an
    // equation dropped into the cell — the fixture PNG stands in for that
    // here), alongside math notation typed straight into the cell text.
    // Word's own equations (OMML) are covered further down.
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

// ---------------------------------------------------------------------------
// Word equations, superscripts and embedded objects. PhpWord can't write
// OMML, so these fixtures are built straight from document.xml.
// ---------------------------------------------------------------------------

function selXml(string $isi): string
{
    return '<w:tc><w:p>'.$isi.'</w:p></w:tc>';
}

function teksXml(string $teks): string
{
    return '<w:r><w:t xml:space="preserve">'.$teks.'</w:t></w:r>';
}

/**
 * @param  array<int, array<int, string>>  $baris  7 cell bodies per row (see selXml()).
 * @param  array<string, string>  $media  rId => file name under word/media/.
 */
function buatDocxXml(array $baris, array $media = []): string
{
    $tabel = '<w:tr>'.implode('', array_map(fn ($judul) => selXml(teksXml($judul)), ['Pertanyaan', 'A', 'B', 'C', 'D', 'E', 'Jawaban'])).'</w:tr>';
    foreach ($baris as $kolom) {
        $tabel .= '<w:tr>'.implode('', array_map('selXml', $kolom)).'</w:tr>';
    }

    $rels = '';
    foreach ($media as $id => $file) {
        $rels .= '<Relationship Id="'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/'.$file.'"/>';
    }

    $path = tempnam(sys_get_temp_dir(), 'soal-word-xml').'.docx';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
        .' xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"'
        .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
        .' xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">'
        .'<w:body><w:tbl>'.$tabel.'</w:tbl></w:body></w:document>');
    $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels.'</Relationships>');
    foreach ($media as $file) {
        $zip->addFromString('word/media/'.$file, 'BYTES');
    }
    $zip->close();

    return $path;
}

/**
 * Pilihan B–D as plain text, E empty, then the jawaban letter.
 *
 * @return array<int, string>
 */
function sisaBaris(string $jawaban = 'B'): array
{
    return [teksXml('2'), teksXml('3'), teksXml('4'), '', teksXml($jawaban)];
}

function ommlXKuadrat(): string
{
    return '<m:oMath><m:sSup><m:e><m:r><m:t>x</m:t></m:r></m:e><m:sup><m:r><m:t>2</m:t></m:r></m:sup></m:sSup><m:r><m:t>+3x=10</m:t></m:r></m:oMath>';
}

function ommlSetengah(): string
{
    return '<m:oMath><m:f><m:num><m:r><m:t>1</m:t></m:r></m:num><m:den><m:r><m:t>2</m:t></m:r></m:den></m:f></m:oMath>';
}

test('a Word equation inside the pertanyaan text is kept as MathML, in place', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxXml([
        [teksXml('Nilai x yang memenuhi ').ommlXKuadrat().teksXml(' adalah'), teksXml('1'), ...sisaBaris()],
    ]);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toBeEmpty()
        ->and($importer->peringatan)->toBeEmpty();

    expect(Soal::sole()->pertanyaan)->toBe(
        '<p>Nilai x yang memenuhi <math><msup><mrow><mi>x</mi></mrow><mrow><mn>2</mn></mrow></msup>'
        .'<mo>+</mo><mn>3</mn><mi>x</mi><mo>=</mo><mn>10</mn></math> adalah</p>'
    );
});

test('a pertanyaan or pilihan holding only an equation is imported', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxXml([
        [ommlXKuadrat(), ommlSetengah(), ...sisaBaris('A')],
    ]);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toBeEmpty();

    $soal = Soal::sole();
    expect($soal->pertanyaan)->toContain('<math>')
        ->and($soal->pilih_a)->toContain('<mfrac><mrow><mn>1</mn></mrow><mrow><mn>2</mn></mrow></mfrac>');
});

test('equation structures convert to the matching MathML', function (string $omml, string $mathml) {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxXml([[teksXml('Soal ').'<m:oMath>'.$omml.'</m:oMath>', teksXml('1'), ...sisaBaris()]]);

    (new SoalWordImport($bankSoal->id))->import($docx);

    expect(Soal::sole()->pertanyaan)->toContain($mathml);
})->with([
    'akar kuadrat' => ['<m:rad><m:radPr><m:degHide m:val="1"/></m:radPr><m:deg/><m:e><m:r><m:t>9</m:t></m:r></m:e></m:rad>', '<msqrt><mrow><mn>9</mn></mrow></msqrt>'],
    'akar pangkat 3' => ['<m:rad><m:deg><m:r><m:t>3</m:t></m:r></m:deg><m:e><m:r><m:t>8</m:t></m:r></m:e></m:rad>', '<mroot><mrow><mn>8</mn></mrow><mrow><mn>3</mn></mrow></mroot>'],
    'subscript' => ['<m:sSub><m:e><m:r><m:t>H</m:t></m:r></m:e><m:sub><m:r><m:t>2</m:t></m:r></m:sub></m:sSub>', '<msub><mrow><mi>H</mi></mrow><mrow><mn>2</mn></mrow></msub>'],
    'kurung siku' => ['<m:d><m:dPr><m:begChr m:val="["/><m:endChr m:val="]"/></m:dPr><m:e><m:r><m:t>a</m:t></m:r></m:e></m:d>', '<mrow><mo>[</mo><mrow><mi>a</mi></mrow><mo>]</mo></mrow>'],
    'sigma' => ['<m:nary><m:naryPr><m:chr m:val="∑"/><m:limLoc m:val="undOvr"/></m:naryPr><m:sub><m:r><m:t>i=1</m:t></m:r></m:sub><m:sup><m:r><m:t>n</m:t></m:r></m:sup><m:e><m:r><m:t>i</m:t></m:r></m:e></m:nary>', '<munderover><mo>∑</mo>'],
    'teks biasa' => ['<m:r><m:rPr><m:nor/></m:rPr><m:t>jika</m:t></m:r>', '<mtext>jika</mtext>'],
]);

test('superscript and subscript formatting on plain text is kept', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxXml([[
        teksXml('Luas = s').'<w:r><w:rPr><w:vertAlign w:val="superscript"/></w:rPr><w:t>2</w:t></w:r>'
            .teksXml(', rumus air H').'<w:r><w:rPr><w:vertAlign w:val="subscript"/></w:rPr><w:t>2</w:t></w:r>'.teksXml('O'),
        teksXml('1'), ...sisaBaris(),
    ]]);

    (new SoalWordImport($bankSoal->id))->import($docx);

    expect(Soal::sole()->pertanyaan)->toBe('<p>Luas = s<sup>2</sup>, rumus air H<sub>2</sub>O</p>');
});

test('a MathType equation fails the row with a clear message instead of saving a broken image', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $mathType = '<w:r><w:object><v:shape id="s1"><v:imagedata r:id="rId9"/></v:shape>'
        .'<o:OLEObject Type="Embed" ProgID="Equation.DSMT4" ShapeID="s1" r:id="rId10"/></w:object></w:r>';
    $docx = buatDocxXml([
        [teksXml('Hitung ').$mathType, teksXml('1'), ...sisaBaris()],
        [teksXml('Baris valid'), teksXml('1'), ...sisaBaris()],
    ], ['rId9' => 'image1.wmf']);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toHaveCount(1)
        ->and($importer->errors[0])->toContain('Baris 2')->toContain('MathType');
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('a picture in a format browsers cannot show fails the row', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $docx = buatDocxXml([
        [teksXml('Perhatikan gambar'), '<w:r><w:pict><v:shape><v:imagedata r:id="rId5"/></v:shape></w:pict></w:r>', ...sisaBaris()],
    ], ['rId5' => 'image1.emf']);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(0)
        ->and($importer->errors[0])->toContain('.emf');
});

test('an unrecognised equation part or a non-equation embedded object is imported with a warning', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $chart = '<w:r><w:object><o:OLEObject Type="Embed" ProgID="Excel.Chart.8" r:id="rId3"/></w:object></w:r>';
    $docx = buatDocxXml([
        [teksXml('Soal ').'<m:oMath><m:zzBaru><m:r><m:t>q</m:t></m:r></m:zzBaru></m:oMath>', teksXml('1'), ...sisaBaris()],
        [teksXml('Soal grafik').$chart, teksXml('1'), ...sisaBaris()],
    ]);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(2)
        ->and($importer->errors)->toBeEmpty()
        ->and($importer->peringatan)->toHaveCount(2)
        ->and($importer->peringatan[0])->toContain('Baris 2')->toContain('zzBaru')
        ->and($importer->peringatan[1])->toContain('Baris 3')->toContain('Excel.Chart.8');
    // The unrecognised part's own text still shows.
    expect(Soal::orderBy('id')->first()->pertanyaan)->toContain('<mi>q</mi>');
});

test('pictures of a row that fails validation are not written to storage', function () {
    Storage::fake('public');
    $bankSoal = BankSoal::factory()->create();
    $gambar = '<w:r><w:pict><v:shape><v:imagedata r:id="rId5"/></v:shape></w:pict></w:r>';
    $docx = buatDocxXml([
        [teksXml('Pertanyaan'), $gambar, teksXml('2'), teksXml('3'), teksXml('4'), '', teksXml('Z')],
    ], ['rId5' => 'image1.png']);

    $importer = new SoalWordImport($bankSoal->id);
    $importer->import($docx);

    expect($importer->imported)->toBe(0);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});
