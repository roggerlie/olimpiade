<?php

use App\Imports\SiswaImport;
use App\Models\Jenjang;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// Unit-style: exercises App\Imports\SiswaImport::collection() directly with a
// plain Collection of rows, sidestepping actual spreadsheet I/O — see
// tests/Feature/Admin/SiswaImportTest.php for the Livewire upload plumbing.
beforeEach(fn () => Role::findOrCreate('siswa', 'web'));

test('it imports valid rows and creates matching login accounts', function () {
    $jenjang = Jenjang::factory()->create(['kode' => '01']);

    $importer = new SiswaImport;
    $importer->collection(collect([
        ['noreg' => '1000001', 'nama' => 'Budi Santoso', 'jenjang' => '01', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
        ['noreg' => '1000002', 'nama' => 'Siti Aminah', 'jenjang' => '01', 'asal_sekolah' => 'SD Contoh', 'password' => 'passwordku'],
    ]));

    expect($importer->imported)->toBe(2)
        ->and($importer->errors)->toBeEmpty()
        ->and(Siswa::count())->toBe(2);

    $budi = Siswa::where('noreg', '1000001')->first();
    expect($budi->jenjang_id)->toBe($jenjang->id)
        ->and($budi->user->hasRole('siswa'))->toBeTrue()
        // password left blank in the sheet defaults to the noreg itself.
        ->and(Hash::check('1000001', $budi->user->password))->toBeTrue();

    $siti = Siswa::where('noreg', '1000002')->first();
    expect(Hash::check('passwordku', $siti->user->password))->toBeTrue();
});

test('it reports invalid rows without aborting the whole import', function () {
    Jenjang::factory()->create(['kode' => '01']);

    $importer = new SiswaImport;
    $importer->collection(collect([
        ['noreg' => '1000001', 'nama' => 'Baris Valid', 'jenjang' => '01', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
        ['noreg' => '', 'nama' => 'Tanpa Noreg', 'jenjang' => '01', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
        ['noreg' => '1000003', 'nama' => 'Jenjang Salah', 'jenjang' => 'XX', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
    ]));

    expect($importer->imported)->toBe(1)
        ->and($importer->errors)->toHaveCount(2)
        ->and(Siswa::count())->toBe(1);
});

test('it skips rows whose noreg already exists', function () {
    Jenjang::factory()->create(['kode' => '01']);
    Siswa::factory()->create(['noreg' => '1000001']);

    $importer = new SiswaImport;
    $importer->collection(collect([
        ['noreg' => '1000001', 'nama' => 'Duplikat', 'jenjang' => '01', 'asal_sekolah' => 'SD Contoh', 'password' => ''],
    ]));

    expect($importer->imported)->toBe(0)
        ->and($importer->errors)->toHaveCount(1)
        ->and(User::where('username', '1000001')->count())->toBe(1);
});
