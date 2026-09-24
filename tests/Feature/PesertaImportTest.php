<?php

use App\Imports\PesertaImport;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Unit-style: exercises App\Imports\PesertaImport::collection() directly with a
// plain Collection of rows, sidestepping actual spreadsheet I/O — see
// tests/Feature/Admin/PesertaImportTest.php for the Livewire upload plumbing.

test('it imports valid rows and creates matching login accounts', function () {
    $jenjang = Jenjang::factory()->create(['nama' => 'SD']);

    $importer = new PesertaImport;
    $importer->collection(collect([
        ['noreg' => '1000000001', 'nama' => 'Budi Santoso', 'jenjang' => 'SD', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
        ['noreg' => '1000000002', 'nama' => 'Siti Aminah', 'jenjang' => 'SD', 'asal_sekolah' => 'SD Contoh', 'password' => 'passwordku'],
    ]));

    expect($importer->imported)->toBe(2)
        ->and($importer->errors)->toBeEmpty()
        ->and(Peserta::count())->toBe(2);

    $budi = Peserta::where('noreg', '1000000001')->first();
    expect($budi->jenjang_id)->toBe($jenjang->id)
        // password left blank in the sheet defaults to the noreg itself.
        ->and(Hash::check('1000000001', $budi->password))->toBeTrue()
        ->and($budi->password_plain)->toBe('1000000001');

    $siti = Peserta::where('noreg', '1000000002')->first();
    expect(Hash::check('passwordku', $siti->password))->toBeTrue()
        ->and($siti->password_plain)->toBe('passwordku');
});

test('a password of "acak" generates a random one, tracked for the admin to read back', function () {
    Jenjang::factory()->create(['nama' => 'SD']);

    $importer = new PesertaImport;
    $importer->collection(collect([
        ['noreg' => '1000000001', 'nama' => 'Budi Santoso', 'jenjang' => 'SD', 'asal_sekolah' => 'SD Contoh', 'password' => 'acak'],
    ]));

    $budi = Peserta::where('noreg', '1000000001')->first();

    expect($importer->generatedPasswords)->toHaveKey('1000000001')
        ->and($budi->password_plain)->toBe($importer->generatedPasswords['1000000001'])
        ->and(Hash::check($budi->password_plain, $budi->password))->toBeTrue()
        // Never literally "acak" — that's the trigger value, not the password.
        ->and($budi->password_plain)->not->toBe('acak');
});

test('it reports invalid rows without aborting the whole import', function () {
    Jenjang::factory()->create(['nama' => 'SD']);

    $importer = new PesertaImport;
    $importer->collection(collect([
        ['noreg' => '1000000001', 'nama' => 'Baris Valid', 'jenjang' => 'SD', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
        ['noreg' => '', 'nama' => 'Tanpa Noreg', 'jenjang' => 'SD', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
        ['noreg' => '1000000003', 'nama' => 'Jenjang Salah', 'jenjang' => 'Antah Berantah', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
    ]));

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toHaveCount(2)
        ->and(Peserta::count())->toBe(1);
});

test('it skips rows whose noreg already exists', function () {
    Jenjang::factory()->create(['nama' => 'SD']);
    Peserta::factory()->create(['noreg' => '1000000001']);

    $importer = new PesertaImport;
    $importer->collection(collect([
        ['noreg' => '1000000001', 'nama' => 'Duplikat', 'jenjang' => 'SD', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
    ]));

    expect($importer->imported)->toBe(0)
        ->and($importer->errors)->toHaveCount(1)
        ->and(Peserta::where('noreg', '1000000001')->count())->toBe(1);
});

test('a competition flag records minat lomba and registers the peserta into every matching ujian', function () {
    $jenjang = Jenjang::factory()->create(['nama' => 'SLTA']);
    $matematika = Pelajaran::factory()->create(['nama' => 'Matematika']);
    $ujian = Ujian::factory()->create(['jenjang_id' => $jenjang->id, 'pelajaran_id' => $matematika->id]);

    $importer = new PesertaImport;
    $importer->collection(collect([
        ['noreg' => '3000000001', 'nama' => 'Budi Santoso', 'jenjang' => 'SLTA', 'asal_sekolah' => 'SMA Contoh', 'password' => '', 'osains' => '0', 'omtk' => '1', 'obing' => '0'],
    ]));

    $budi = Peserta::where('noreg', '3000000001')->first();

    expect($importer->notes)->toBeEmpty()
        ->and($budi->pelajaranLomba->pluck('id')->all())->toBe([$matematika->id])
        ->and($budi->pesertaUjian()->where('ujian_id', $ujian->id)->exists())->toBeTrue();
});

test('a competition flag with no matching ujian yet still records minat lomba, just with a note', function () {
    Jenjang::factory()->create(['nama' => 'SLTA']);
    $sains = Pelajaran::factory()->create(['nama' => 'Sains']);

    $importer = new PesertaImport;
    $importer->collection(collect([
        ['noreg' => '3000000001', 'nama' => 'Budi Santoso', 'jenjang' => 'SLTA', 'asal_sekolah' => 'SMA Contoh', 'password' => '', 'osains' => '1', 'omtk' => '0', 'obing' => '0'],
    ]));

    $budi = Peserta::where('noreg', '3000000001')->first();

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toBeEmpty()
        ->and($importer->notes)->toHaveCount(1)
        // The point of minat lomba: recorded even with no ujian to register into yet.
        ->and($budi->pelajaranLomba->pluck('id')->all())->toBe([$sains->id])
        ->and($budi->pesertaUjian()->count())->toBe(0);
});
