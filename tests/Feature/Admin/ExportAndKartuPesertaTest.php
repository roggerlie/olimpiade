<?php

use App\Exports\PesertaUjianExport;
use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('it downloads nilai for the given ujian only', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create(['nama' => 'Ujian Matematika']);
    $otherUjian = Ujian::factory()->create();

    $peserta = Peserta::factory()->create(['noreg' => '1000000001', 'nama' => 'Budi Santoso']);
    PesertaUjian::factory()->selesai()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);
    PesertaUjian::factory()->selesai()->create(['ujian_id' => $otherUjian->id]);

    Excel::fake();

    $this->get(route('admin.ujian.peserta.export', $ujian))->assertOk();

    Excel::assertDownloaded("Nilai-{$ujian->nama}.xlsx", function (PesertaUjianExport $export) use ($peserta) {
        $rows = $export->query()->get();

        return $rows->count() === 1 && $rows->first()->peserta_id === $peserta->id;
    });
});

test('kartu peserta index lists all jenjang as filter options', function () {
    actingAsAdmin();
    Jenjang::factory()->create(['nama' => 'Sekolah Dasar']);

    $this->get(route('admin.kartu-peserta.index'))
        ->assertOk()
        ->assertSee('Sekolah Dasar');
});

test('kartu peserta cetak shows only peserta from the selected jenjang', function () {
    actingAsAdmin();
    $jenjangA = Jenjang::factory()->create();
    $jenjangB = Jenjang::factory()->create();
    Peserta::factory()->create(['jenjang_id' => $jenjangA->id, 'noreg' => '1000000001', 'nama' => 'Peserta A']);
    Peserta::factory()->create(['jenjang_id' => $jenjangB->id, 'noreg' => '2000000002', 'nama' => 'Peserta B']);

    $this->get(route('admin.kartu-peserta.cetak', ['jenjang' => $jenjangA->id]))
        ->assertOk()
        ->assertSee('Peserta A')
        ->assertSee('1000000001')
        ->assertDontSee('Peserta B');
});

test('kartu peserta cetak shows the peserta\'s plaintext password', function () {
    actingAsAdmin();
    Peserta::factory()->create(['nama' => 'Budi Santoso', 'password_plain' => 'rahasia123']);

    $this->get(route('admin.kartu-peserta.cetak'))
        ->assertOk()
        ->assertSee('rahasia123');
});

test('kartu peserta cetak without a jenjang filter shows every peserta', function () {
    actingAsAdmin();
    Peserta::factory()->create(['nama' => 'Peserta A']);
    Peserta::factory()->create(['nama' => 'Peserta B']);

    $this->get(route('admin.kartu-peserta.cetak'))
        ->assertOk()
        ->assertSee('Peserta A')
        ->assertSee('Peserta B');
});
