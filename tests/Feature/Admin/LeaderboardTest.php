<?php

use App\Exports\LeaderboardExport;
use App\Models\SiswaUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('the leaderboard page lists participants ranked by nilai desc', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create(['nama' => 'Ujian Matematika']);
    $tinggi = SiswaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 81]);
    $rendah = SiswaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 18]);

    $response = $this->get(route('admin.ujian.leaderboard', $ujian))->assertOk();

    $content = $response->getContent();
    $posTinggi = strpos($content, $tinggi->siswa->nama);
    $posRendah = strpos($content, $rendah->siswa->nama);

    expect($posTinggi)->not->toBeFalse()
        ->and($posRendah)->not->toBeFalse()
        ->and($posTinggi)->toBeLessThan($posRendah);
});

test('the leaderboard page shows an empty state when nobody has finished', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create();

    $this->get(route('admin.ujian.leaderboard', $ujian))
        ->assertOk()
        ->assertSee('Belum ada peserta yang menyelesaikan ujian ini.');
});

test('leaderboard export downloads the ranked results for the given ujian', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create(['nama' => 'Ujian Matematika']);
    $tinggi = SiswaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 81]);
    SiswaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 18]);
    SiswaUjian::factory()->selesai()->create(); // different ujian entirely

    Excel::fake();

    $this->get(route('admin.ujian.leaderboard.export', $ujian))->assertOk();

    Excel::assertDownloaded("Leaderboard-{$ujian->nama}.xlsx", function (LeaderboardExport $export) use ($tinggi) {
        $rows = $export->collection();

        return $rows->count() === 2 && $rows->first()->id === $tinggi->id;
    });
});
