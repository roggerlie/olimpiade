<?php

use App\Exports\PesertaExport;
use App\Exports\PesertaUjianExport;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use App\Models\PesertaUjian;
use App\Models\Ruangan;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

test('kartu peserta index lists all jenjang and pelajaran as filter options', function () {
    actingAsAdmin();
    Jenjang::factory()->create(['nama' => 'Sekolah Dasar']);
    Pelajaran::factory()->create(['nama' => 'Bahasa Inggris']);

    $this->get(route('admin.kartu-peserta.index'))
        ->assertOk()
        ->assertSee('Sekolah Dasar')
        ->assertSee('Bahasa Inggris');
});

test('kartu peserta cetak shows only peserta with the selected pelajaran', function () {
    actingAsAdmin();
    $matematika = Pelajaran::factory()->create();
    Peserta::factory()->create(['nama' => 'Peserta Matematika'])->pelajaranLomba()->attach($matematika->id);
    Peserta::factory()->create(['nama' => 'Peserta Lain'])->pelajaranLomba()->attach(Pelajaran::factory()->create()->id);

    $this->get(route('admin.kartu-peserta.cetak', ['pelajaran' => $matematika->id]))
        ->assertOk()
        ->assertSee('Peserta Matematika')
        ->assertDontSee('Peserta Lain');
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

test('kartu peserta cetak lists each registered lomba with its own ruangan', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create();
    $ipa = Ujian::factory()->create(['pelajaran_id' => Pelajaran::factory()->create(['nama' => 'IPA'])->id]);
    $mtk = Ujian::factory()->create(['pelajaran_id' => Pelajaran::factory()->create(['nama' => 'MATEMATIKA'])->id]);
    PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ipa->id, 'ruangan_id' => Ruangan::factory()->create(['nama' => 'Lab Satu'])->id]);
    PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $mtk->id]);

    $this->get(route('admin.kartu-peserta.cetak'))
        ->assertOk()
        ->assertSee('IPA')
        ->assertSee('Lab Satu')
        ->assertSee('MATEMATIKA')
        ->assertSee('Ruangan belum ditentukan');
});

test('nilai export includes each peserta\'s ruangan', function () {
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['ruangan_id' => Ruangan::factory()->create(['nama' => 'Lab Satu'])->id]);
    $export = new PesertaUjianExport($pesertaUjian->ujian_id);

    $baris = $export->map($export->query()->first());

    expect($baris[array_search('Ruangan', $export->headings(), true)])->toBe('Lab Satu');
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

test('peserta export downloads only peserta matching the list filters, named after the jenjang', function () {
    actingAsAdmin();
    $this->travelTo(now()->setDate(2026, 10, 5));
    $slta = Jenjang::factory()->create(['nama' => 'SLTA']);
    $budi = Peserta::factory()->create(['jenjang_id' => $slta->id, 'nama' => 'Budi Santoso']);
    Peserta::factory()->create(['jenjang_id' => $slta->id, 'nama' => 'Siti Aminah']);
    Peserta::factory()->create(['nama' => 'Budi Lain Jenjang']);

    Excel::fake();

    $this->get(route('admin.peserta.export', ['q' => 'Budi', 'jenjang' => $slta->id]))->assertOk();

    Excel::assertDownloaded('Peserta-SLTA-2026-10-05.xlsx', function (PesertaExport $export) use ($budi) {
        return $export->query()->pluck('id')->all() === [$budi->id];
    });
});

test('peserta export filtered by pelajaran downloads only that pelajaran\'s peserta, named after it', function () {
    actingAsAdmin();
    $this->travelTo(now()->setDate(2026, 10, 5));
    $matematika = Pelajaran::factory()->create(['nama' => 'MATEMATIKA']);
    $budi = Peserta::factory()->create();
    $budi->pelajaranLomba()->attach($matematika->id);
    Peserta::factory()->create()->pelajaranLomba()->attach(Pelajaran::factory()->create()->id);

    Excel::fake();

    $this->get(route('admin.peserta.export', ['pelajaran' => $matematika->id]))->assertOk();

    Excel::assertDownloaded('Peserta-MATEMATIKA-2026-10-05.xlsx', function (PesertaExport $export) use ($budi) {
        return $export->query()->pluck('id')->all() === [$budi->id];
    });
});

test('peserta export with ticked ids downloads exactly those peserta, ignoring the list filters', function () {
    actingAsAdmin();
    $this->travelTo(now()->setDate(2026, 10, 5));
    [$dicentang, $lainDicentang, $tidakDicentang] = Peserta::factory()->count(3)->create()->all();

    Excel::fake();

    $this->get(route('admin.peserta.export', [
        'ids' => [$dicentang->id, $lainDicentang->id],
        'q' => $tidakDicentang->nama,
    ]))->assertOk();

    Excel::assertDownloaded('Peserta-Terpilih-2026-10-05.xlsx', function (PesertaExport $export) use ($dicentang, $lainDicentang) {
        return $export->query()->pluck('id')->sort()->values()->all() === collect([$dicentang->id, $lainDicentang->id])->sort()->values()->all();
    });
});

test('peserta export writes login credentials as text so a NISN keeps its leading zero', function () {
    $jenjang = Jenjang::factory()->create(['nama' => 'SD']);
    $peserta = Peserta::factory()->create([
        'jenjang_id' => $jenjang->id,
        'noreg' => '0123456789',
        'nama' => 'Budi Santoso',
        'password_plain' => '346979',
    ]);
    $peserta->pelajaranLomba()->attach(Pelajaran::factory()->create(['nama' => 'IPA']));

    $path = tempnam(sys_get_temp_dir(), 'peserta-export').'.xlsx';
    file_put_contents($path, Excel::raw(new PesertaExport, ExcelWriter::XLSX));
    $sheet = IOFactory::load($path)->getActiveSheet();
    unlink($path);

    expect($sheet->rangeToArray('A2:G2')[0])->toBe(['1', '0123456789', 'Budi Santoso', 'SD', $peserta->asal_sekolah, '346979', 'IPA'])
        ->and($sheet->getCell('B2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($sheet->getCell('F2')->getDataType())->toBe(DataType::TYPE_STRING);
});

test('a logged-in user without an admin role cannot export peserta credentials', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.peserta.export'))->assertForbidden();
});
