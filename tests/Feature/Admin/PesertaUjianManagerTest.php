<?php

use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Ruangan;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function ujianForJenjang(Jenjang $jenjang): Ujian
{
    return Ujian::factory()->create(['jenjang_id' => $jenjang->id]);
}

test('it only lists peserta belonging to the ujian jenjang', function () {
    actingAsAdmin();
    $jenjangA = Jenjang::factory()->create();
    $jenjangB = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjangA);
    Peserta::factory()->create(['jenjang_id' => $jenjangA->id, 'nama' => 'Peserta Jenjang A']);
    Peserta::factory()->create(['jenjang_id' => $jenjangB->id, 'nama' => 'Peserta Jenjang B']);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->assertSee('Peserta Jenjang A')
        ->assertDontSee('Peserta Jenjang B');
});

test('it registers a single unregistered peserta', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $peserta = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('toggleDaftar', $peserta->id);

    expect(PesertaUjian::where('peserta_id', $peserta->id)->where('ujian_id', $ujian->id)->exists())->toBeTrue();
});

test('it unregisters a peserta who has not started the exam', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $peserta = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('toggleDaftar', $peserta->id);

    expect(PesertaUjian::where('peserta_id', $peserta->id)->where('ujian_id', $ujian->id)->exists())->toBeFalse();
});

test('it refuses to unregister a peserta who already started the exam', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $peserta = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id, 'waktu_mulai' => now()]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('toggleDaftar', $peserta->id)
        ->assertSet('errorMessage', fn ($message) => str_contains($message, 'sudah mulai mengerjakan'));

    expect(PesertaUjian::where('peserta_id', $peserta->id)->where('ujian_id', $ujian->id)->exists())->toBeTrue();
});

test('daftarkanYangBerminat only registers peserta who declared interest in this ujian mapel', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);

    $berminat = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    $berminat->pelajaranLomba()->attach($ujian->pelajaran_id);

    $tidakBerminat = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('daftarkanYangBerminat')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, '1 peserta'));

    expect(PesertaUjian::where('peserta_id', $berminat->id)->where('ujian_id', $ujian->id)->exists())->toBeTrue()
        ->and(PesertaUjian::where('peserta_id', $tidakBerminat->id)->where('ujian_id', $ujian->id)->exists())->toBeFalse();
});

test('daftarkanYangBerminat skips a peserta already registered', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);

    $sudahTerdaftar = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    $sudahTerdaftar->pelajaranLomba()->attach($ujian->pelajaran_id);
    PesertaUjian::factory()->create(['peserta_id' => $sudahTerdaftar->id, 'ujian_id' => $ujian->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('daftarkanYangBerminat')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, '0 peserta'));

    expect(PesertaUjian::where('ujian_id', $ujian->id)->count())->toBe(1);
});

test('resetProgres wipes timing, score and answers but keeps the registration', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $peserta = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);
    PesertaSoal::factory()->count(3)->create(['peserta_ujian_id' => $pesertaUjian->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('resetProgres', $peserta->id)
        ->assertSet('statusMessage', fn ($message) => str_contains($message, 'direset'));

    $pesertaUjian->refresh();
    expect($pesertaUjian->waktu_mulai)->toBeNull()
        ->and($pesertaUjian->waktu_selesai)->toBeNull()
        ->and($pesertaUjian->benar)->toBeNull()
        ->and($pesertaUjian->salah)->toBeNull()
        ->and($pesertaUjian->nilai)->toBeNull()
        ->and(PesertaSoal::where('peserta_ujian_id', $pesertaUjian->id)->count())->toBe(0)
        // still registered — resetProgres only clears progress, not the enrolment.
        ->and(PesertaUjian::where('id', $pesertaUjian->id)->exists())->toBeTrue();
});

test('resetSemua only touches attempts that have actually started', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);

    $sudahMulai = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    $attemptMulai = PesertaUjian::factory()->selesai()->create(['peserta_id' => $sudahMulai->id, 'ujian_id' => $ujian->id]);
    PesertaSoal::factory()->count(2)->create(['peserta_ujian_id' => $attemptMulai->id]);

    $belumMulai = Peserta::factory()->create(['jenjang_id' => $jenjang->id]);
    PesertaUjian::factory()->create(['peserta_id' => $belumMulai->id, 'ujian_id' => $ujian->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('resetSemua')
        ->assertSet('statusMessage', fn ($message) => str_contains($message, '1 progres'));

    expect($attemptMulai->fresh()->waktu_mulai)->toBeNull()
        ->and(PesertaSoal::where('peserta_ujian_id', $attemptMulai->id)->count())->toBe(0);

    $belumMulaiAttempt = PesertaUjian::where('peserta_id', $belumMulai->id)->first();
    expect($belumMulaiAttempt)->not->toBeNull()
        ->and($belumMulaiAttempt->waktu_mulai)->toBeNull();
});

test('the per-row ruangan dropdown places and unplaces a registered peserta', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $ruangan = Ruangan::factory()->create(['nama' => 'Lab 1']);
    $pesertaUjian = PesertaUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'peserta_id' => Peserta::factory()->create(['jenjang_id' => $jenjang->id])->id,
    ]);

    $component = Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('pindahkanRuangan', $pesertaUjian->peserta_id, (string) $ruangan->id);

    expect($pesertaUjian->fresh()->ruangan_id)->toBe($ruangan->id);

    $component->call('pindahkanRuangan', $pesertaUjian->peserta_id, '');

    expect($pesertaUjian->fresh()->ruangan_id)->toBeNull();
});

test('pindahkan terpilih moves only the ticked peserta, and only for this ujian', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $ujianLain = ujianForJenjang($jenjang);
    $ruangan = Ruangan::factory()->create(['nama' => 'Lab 1']);
    [$dicentang, $tidakDicentang] = Peserta::factory()->count(2)->create(['jenjang_id' => $jenjang->id])->all();
    $daftarDicentang = PesertaUjian::factory()->create(['ujian_id' => $ujian->id, 'peserta_id' => $dicentang->id]);
    $daftarLain = PesertaUjian::factory()->create(['ujian_id' => $ujian->id, 'peserta_id' => $tidakDicentang->id]);
    $dicentangDiUjianLain = PesertaUjian::factory()->create(['ujian_id' => $ujianLain->id, 'peserta_id' => $dicentang->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->set('selectedIds', [(string) $dicentang->id])
        ->set('targetRuangan', (string) $ruangan->id)
        ->call('pindahkanTerpilih')
        ->assertSet('statusMessage', '1 peserta dipindahkan ke Lab 1.')
        ->assertSet('selectedIds', []);

    expect($daftarDicentang->fresh()->ruangan_id)->toBe($ruangan->id)
        ->and($daftarLain->fresh()->ruangan_id)->toBeNull()
        ->and($dicentangDiUjianLain->fresh()->ruangan_id)->toBeNull();
});

test('pindahkan terpilih needs both ticked peserta and a target ruangan', function () {
    actingAsAdmin();
    $ujian = ujianForJenjang(Jenjang::factory()->create());

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('pindahkanTerpilih')
        ->assertSet('errorMessage', 'Centang peserta dan pilih ruangan tujuannya dulu.');
});

test('the ruangan filter lists only peserta in that ruangan, or those not placed yet', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $ruangan = Ruangan::factory()->create();
    PesertaUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'ruangan_id' => $ruangan->id,
        'peserta_id' => Peserta::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Di Ruangan'])->id,
    ]);
    PesertaUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'peserta_id' => Peserta::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Tanpa Ruangan'])->id,
    ]);
    Peserta::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Belum Terdaftar']);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->set('filterRuangan', (string) $ruangan->id)
        ->assertSee('Di Ruangan')
        ->assertDontSee('Tanpa Ruangan')
        ->assertDontSee('Belum Terdaftar')
        ->set('filterRuangan', 'tanpa')
        ->assertSee('Tanpa Ruangan')
        ->assertDontSee('Di Ruangan')
        ->assertDontSee('Belum Terdaftar');
});

test('the ruangan summary warns when a room is over kapasitas, without blocking', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $ruangan = Ruangan::factory()->create(['nama' => 'Lab Kecil', 'kapasitas' => 1]);
    PesertaUjian::factory()->count(2)->create([
        'ujian_id' => $ujian->id,
        'ruangan_id' => $ruangan->id,
        'peserta_id' => fn () => Peserta::factory()->create(['jenjang_id' => $jenjang->id])->id,
    ]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->assertSeeHtml('<span class="font-semibold">Lab Kecil</span>: 2/1')
        ->assertSee('melebihi kapasitas');
});

test('bagi otomatis requires at least one ruangan', function () {
    actingAsAdmin();
    $ujian = ujianForJenjang(Jenjang::factory()->create());

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('bukaBagiOtomatis')
        ->assertSet('showBagiModal', true)
        ->call('bagiOtomatis')
        ->assertHasErrors(['bagiRuanganIds' => 'required']);
});

test('bagi otomatis places unplaced peserta and reports who did not fit', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $ujian = ujianForJenjang($jenjang);
    $ruangan = Ruangan::factory()->create(['kapasitas' => 2]);
    PesertaUjian::factory()->count(3)->create([
        'ujian_id' => $ujian->id,
        'peserta_id' => fn () => Peserta::factory()->create(['jenjang_id' => $jenjang->id])->id,
    ]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->call('bukaBagiOtomatis')
        ->set('bagiRuanganIds', [(string) $ruangan->id])
        ->call('bagiOtomatis')
        ->assertHasNoErrors()
        ->assertSet('showBagiModal', false)
        ->assertSet('statusMessage', '2 peserta dibagi ke ruangan.')
        ->assertSet('errorMessage', fn (string $message) => str_starts_with($message, '1 peserta belum kebagian ruangan'));

    expect(PesertaUjian::where('ruangan_id', $ruangan->id)->count())->toBe(2);
});

test('daftar hadir prints one sheet per ruangan, leaving out peserta not placed yet', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create();
    $labA = Ruangan::factory()->create(['nama' => 'Lab A']);
    $labB = Ruangan::factory()->create(['nama' => 'Lab B']);
    PesertaUjian::factory()->create(['ujian_id' => $ujian->id, 'ruangan_id' => $labA->id, 'peserta_id' => Peserta::factory()->create(['nama' => 'Peserta Lab A'])->id]);
    PesertaUjian::factory()->create(['ujian_id' => $ujian->id, 'ruangan_id' => $labB->id, 'peserta_id' => Peserta::factory()->create(['nama' => 'Peserta Lab B'])->id]);
    PesertaUjian::factory()->create(['ujian_id' => $ujian->id, 'peserta_id' => Peserta::factory()->create(['nama' => 'Peserta Tanpa Ruangan'])->id]);

    $this->get(route('admin.ujian.daftar-hadir', $ujian))
        ->assertOk()
        ->assertSeeInOrder(['Lab A', 'Peserta Lab A', 'Lab B', 'Peserta Lab B'])
        ->assertDontSee('Peserta Tanpa Ruangan')
        ->assertSee('1 peserta terdaftar belum punya ruangan');

    $this->get(route('admin.ujian.daftar-hadir', [$ujian, 'ruangan' => $labB->id]))
        ->assertOk()
        ->assertSee('Peserta Lab B')
        ->assertDontSee('Peserta Lab A');
});
