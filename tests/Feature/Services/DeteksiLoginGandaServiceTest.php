<?php

use App\Models\Peserta;
use App\Models\User;
use App\Services\DeteksiLoginGandaService;
use App\Support\LogAktivitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function loginDari(Peserta|User $siapa, string $ip, string $perangkat = 'Chrome Lab'): void
{
    LogAktivitas::catat(LogAktivitas::AUTH, 'login', 'Login berhasil', $siapa, properti: [
        'guard' => $siapa instanceof Peserta ? 'peserta' : 'web',
        'ip' => $ip,
        'user_agent' => $perangkat,
    ]);
}

test('a peserta who logged in from two IPs is flagged, one who stayed put is not', function () {
    $pindah = Peserta::factory()->create(['nama' => 'Budi']);
    $tetap = Peserta::factory()->create(['nama' => 'Citra']);

    loginDari($pindah, '10.0.0.1');
    loginDari($pindah, '10.0.0.2');
    loginDari($tetap, '10.0.0.3');
    loginDari($tetap, '10.0.0.3');

    $hasil = (new DeteksiLoginGandaService)->pesertaBanyakPerangkat(today()->toDateString(), today()->toDateString());

    expect($hasil)->toHaveCount(1)
        ->and($hasil[0]['peserta']->is($pindah))->toBeTrue()
        ->and($hasil[0]['ip'])->toBe(['10.0.0.1', '10.0.0.2'])
        ->and($hasil[0]['jumlahLogin'])->toBe(2);
});

test('the same IP with a different browser counts as another device', function () {
    $peserta = Peserta::factory()->create();

    loginDari($peserta, '10.0.0.1', 'Chrome');
    loginDari($peserta, '10.0.0.1', 'Firefox');

    expect((new DeteksiLoginGandaService)->pesertaBanyakPerangkat(today()->toDateString(), today()->toDateString()))->toHaveCount(1);
});

test('one device used by two peserta is flagged', function () {
    $budi = Peserta::factory()->create(['nama' => 'Budi', 'noreg' => '1000000001']);
    $citra = Peserta::factory()->create(['nama' => 'Citra', 'noreg' => '1000000002']);

    loginDari($budi, '10.0.0.1');
    loginDari($citra, '10.0.0.1');
    loginDari($budi, '10.0.0.9', 'Lain');

    $hasil = (new DeteksiLoginGandaService)->perangkatBanyakPeserta(today()->toDateString(), today()->toDateString());

    expect($hasil)->toHaveCount(1)
        ->and($hasil[0]['ip'])->toBe('10.0.0.1')
        ->and($hasil[0]['pelaku'])->toBe(['Budi (1000000001)', 'Citra (1000000002)']);
});

test('admin logins and logins outside the date range are ignored', function () {
    $admin = User::factory()->create();
    $peserta = Peserta::factory()->create();

    loginDari($admin, '10.0.0.1');
    loginDari($admin, '10.0.0.2');

    $this->travelTo(now()->subDays(3));
    loginDari($peserta, '10.0.0.5');
    $this->travelBack();
    loginDari($peserta, '10.0.0.6');

    $service = new DeteksiLoginGandaService;

    expect($service->pesertaBanyakPerangkat(today()->toDateString(), today()->toDateString()))->toBeEmpty()
        ->and($service->perangkatBanyakPeserta(today()->toDateString(), today()->toDateString()))->toBeEmpty();
});

test('the Deteksi tab lists flagged peserta', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['nama' => 'Budi Santoso']);
    loginDari($peserta, '10.0.0.1');
    loginDari($peserta, '10.0.0.2');

    Livewire::test('admin.log-aktivitas.deteksi')
        ->assertSee('Budi Santoso')
        ->assertSee('2 IP');
});
