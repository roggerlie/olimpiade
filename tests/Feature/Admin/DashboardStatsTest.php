<?php

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
