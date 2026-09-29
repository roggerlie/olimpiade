<?php

use App\Enums\KategoriReset;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

/**
 * actingAsAdmin() doesn't set a known password — this one does, for the
 * `current_password` confirmation step.
 */
function actingAsAdministratorDenganPassword(string $password = 'rahasia123'): User
{
    $admin = actingAsAdmin();
    $admin->update(['password' => $password]);

    return $admin;
}

test('only administrator can open the pengaturan page', function (string $role, int $status) {
    actingAsAdminRole($role);

    $this->get(route('admin.pengaturan.index'))->assertStatus($status);
})->with([
    'admin' => ['admin', 403],
    'operator' => ['operator', 403],
]);

test('administrator can open the pengaturan page', function () {
    actingAsAdmin();

    $this->get(route('admin.pengaturan.index'))
        ->assertOk()
        ->assertSeeLivewire('admin.pengaturan.reset-data');
});

test('the component itself refuses non-administrators', function () {
    actingAsAdminRole('admin');

    Livewire::test('admin.pengaturan.reset-data')->assertForbidden();
});

test('ticking a category locks in its dependencies', function () {
    actingAsAdmin();

    $component = Livewire::test('admin.pengaturan.reset-data')->set('dipilih', ['bank-soal']);

    expect($component->instance()->kategoriTerpilih)
        ->toBe([KategoriReset::HasilUjian, KategoriReset::Ujian, KategoriReset::BankSoal])
        ->and($component->instance()->otomatis)
        ->toBe(['hasil-ujian' => 'Bank Soal', 'ujian' => 'Bank Soal']);
});

test('it rejects a wrong confirmation phrase or password, deleting nothing', function () {
    actingAsAdministratorDenganPassword();
    Jenjang::factory()->create();

    Livewire::test('admin.pengaturan.reset-data')
        ->set('dipilih', ['master-data'])
        ->call('konfirmasi')
        ->set('frasa', 'hapus data')
        ->set('password', 'salah')
        ->call('jalankan')
        ->assertHasErrors(['frasa' => 'in', 'password' => 'current_password']);

    expect(Jenjang::count())->toBe(1);
});

test('it wipes the chosen data once confirmed and offers the backup', function () {
    actingAsAdministratorDenganPassword();
    Jenjang::factory()->create();
    Pelajaran::factory()->create();

    $component = Livewire::test('admin.pengaturan.reset-data')
        ->set('dipilih', ['master-data'])
        ->call('konfirmasi')
        ->assertSet('showKonfirmasi', true)
        ->set('frasa', 'HAPUS DATA')
        ->set('password', 'rahasia123')
        ->call('jalankan')
        ->assertHasNoErrors()
        ->assertSet('showKonfirmasi', false)
        ->assertSet('dipilih', []);

    expect(Jenjang::count() + Pelajaran::count())->toBe(0);

    $backup = $component->get('hasil')['backup'];
    Storage::disk('local')->assertExists($backup);
    $component->assertSee(basename($backup));
});

test('confirmation is rate limited after three failed attempts', function () {
    actingAsAdministratorDenganPassword();

    $component = Livewire::test('admin.pengaturan.reset-data')->set('dipilih', ['peserta'])->call('konfirmasi');

    foreach (range(1, 3) as $_) {
        $component->set('frasa', 'HAPUS DATA')->set('password', 'salah')->call('jalankan');
    }

    $component->set('password', 'rahasia123')->call('jalankan')
        ->assertHasErrors('password');
    expect($component->errors()->first('password'))->toContain('Terlalu banyak percobaan');
});

test('backups can be downloaded by administrator only, by exact name', function () {
    Storage::disk('local')->put('backups/reset-20260929_101500.xlsx', 'isi');

    actingAsAdmin();
    $this->get(route('admin.pengaturan.backup', 'reset-20260929_101500.xlsx'))->assertDownload('reset-20260929_101500.xlsx');
    $this->get(route('admin.pengaturan.backup', 'reset-20260101_000000.xlsx'))->assertNotFound();
    $this->get('/admin/pengaturan/backup/..%2F.env')->assertNotFound();

    actingAsAdminRole('operator');
    $this->get(route('admin.pengaturan.backup', 'reset-20260929_101500.xlsx'))->assertForbidden();
});

test('the artisan command resets the given categories when forced', function () {
    Peserta::factory()->create();

    $this->artisan('olimpiade:reset-data', ['--only' => 'peserta', '--force' => true])->assertSuccessful();

    expect(Peserta::count())->toBe(0);
});

test('the artisan command rejects unknown categories and refuses production without --force', function () {
    Peserta::factory()->create();

    $this->artisan('olimpiade:reset-data', ['--only' => 'semua'])->assertExitCode(2);

    app()->detectEnvironment(fn () => 'production');
    $this->artisan('olimpiade:reset-data', ['--only' => 'peserta'])->assertFailed();

    expect(Peserta::count())->toBe(1);
});
