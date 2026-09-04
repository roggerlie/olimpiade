<?php

use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it counts total peserta and ujian currently in session', function () {
    actingAsAdmin();
    Peserta::factory()->count(3)->create();
    Ujian::factory()->create(['sesi_mulai' => now()->subHour(), 'sesi_selesai' => now()->addHour()]);
    Ujian::factory()->create(['sesi_mulai' => now()->addDay(), 'sesi_selesai' => now()->addDay()->addHours(2)]);

    Livewire::test('admin.dashboard-stats')
        ->assertSet('totalPeserta', 3)
        ->assertSet('ujianBerlangsung', 1);
});

test('rata-rata nilai is null until at least one peserta_ujian has a score', function () {
    actingAsAdmin();

    Livewire::test('admin.dashboard-stats')->assertSet('rataRataNilai', null);

    PesertaUjian::factory()->selesai()->create(['nilai' => 80]);
    PesertaUjian::factory()->selesai()->create(['nilai' => 60]);

    Livewire::test('admin.dashboard-stats')->assertSet('rataRataNilai', 70.0);
});

test('peserta per jenjang lists every jenjang, including ones with zero peserta', function () {
    actingAsAdmin();
    $sd = Jenjang::factory()->create(['nama' => 'SD']);
    Jenjang::factory()->create(['nama' => 'SMA']);
    Peserta::factory()->count(2)->create(['jenjang_id' => $sd->id]);

    $result = Livewire::test('admin.dashboard-stats')
        ->instance()
        ->pesertaPerJenjang()
        ->keyBy('label');

    expect($result['SD']['total'])->toBe(2)
        ->and($result['SMA']['total'])->toBe(0);
});

test('rata nilai per ujian only includes ujian with a scored attempt, highest first', function () {
    actingAsAdmin();
    $tinggi = Ujian::factory()->create(['nama' => 'Ujian Tinggi']);
    $rendah = Ujian::factory()->create(['nama' => 'Ujian Rendah']);
    $belum = Ujian::factory()->create(['nama' => 'Ujian Belum Selesai']);

    PesertaUjian::factory()->selesai()->create(['ujian_id' => $tinggi->id, 'nilai' => 90]);
    PesertaUjian::factory()->selesai()->create(['ujian_id' => $rendah->id, 'nilai' => 40]);
    PesertaUjian::factory()->create(['ujian_id' => $belum->id]); // not yet submitted

    $result = Livewire::test('admin.dashboard-stats')->instance()->rataNilaiPerUjian();

    expect($result->pluck('label')->all())->toBe(['Ujian Tinggi', 'Ujian Rendah'])
        ->and($result->pluck('total')->all())->toBe([90.0, 40.0]);
});
