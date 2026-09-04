<?php

use App\Exports\SiswaUjianExport;
use App\Models\Jenjang;
use App\Models\Siswa;
use App\Models\SiswaUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('it downloads nilai for the given ujian only', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create(['nama' => 'Ujian Matematika']);
    $otherUjian = Ujian::factory()->create();

    $siswa = Siswa::factory()->create(['noreg' => '1000001', 'nama' => 'Budi Santoso']);
    SiswaUjian::factory()->selesai()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);
    SiswaUjian::factory()->selesai()->create(['ujian_id' => $otherUjian->id]);

    Excel::fake();

    $this->get(route('admin.ujian.peserta.export', $ujian))->assertOk();

    Excel::assertDownloaded("Nilai-{$ujian->nama}.xlsx", function (SiswaUjianExport $export) use ($siswa) {
        $rows = $export->query()->get();

        return $rows->count() === 1 && $rows->first()->siswa_id === $siswa->id;
    });
});

test('kartu peserta index lists all jenjang as filter options', function () {
    actingAsAdmin();
    Jenjang::factory()->create(['nama' => 'Sekolah Dasar']);

    $this->get(route('admin.kartu-peserta.index'))
        ->assertOk()
        ->assertSee('Sekolah Dasar');
});

test('kartu peserta cetak shows only siswa from the selected jenjang', function () {
    actingAsAdmin();
    $jenjangA = Jenjang::factory()->create();
    $jenjangB = Jenjang::factory()->create();
    Siswa::factory()->create(['jenjang_id' => $jenjangA->id, 'noreg' => '1000001', 'nama' => 'Peserta A']);
    Siswa::factory()->create(['jenjang_id' => $jenjangB->id, 'noreg' => '2000002', 'nama' => 'Peserta B']);

    $this->get(route('admin.kartu-peserta.cetak', ['jenjang' => $jenjangA->id]))
        ->assertOk()
        ->assertSee('Peserta A')
        ->assertSee('1000001')
        ->assertDontSee('Peserta B');
});

test('kartu peserta cetak without a jenjang filter shows every siswa', function () {
    actingAsAdmin();
    Siswa::factory()->create(['nama' => 'Peserta A']);
    Siswa::factory()->create(['nama' => 'Peserta B']);

    $this->get(route('admin.kartu-peserta.cetak'))
        ->assertOk()
        ->assertSee('Peserta A')
        ->assertSee('Peserta B');
});
