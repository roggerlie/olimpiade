<?php

use App\Exports\LeaderboardExport;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('the leaderboard page lists participants ranked by nilai desc', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create(['nama' => 'Ujian Matematika']);
    $tinggi = PesertaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 81]);
    $rendah = PesertaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 18]);

    $response = $this->get(route('admin.ujian.leaderboard', $ujian))->assertOk();

    // e() to match Blade's HTML-escaped output — a Faker-generated nama with
    // an apostrophe (e.g. "O'Connor") renders as "O&#039;Connor", so a raw
    // strpos() intermittently missed it and flaked this test.
    $content = $response->getContent();
    $posTinggi = strpos($content, e($tinggi->peserta->nama));
    $posRendah = strpos($content, e($rendah->peserta->nama));

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
    $tinggi = PesertaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 81]);
    PesertaUjian::factory()->selesai()->create(['ujian_id' => $ujian->id, 'nilai' => 18]);
    PesertaUjian::factory()->selesai()->create(); // different ujian entirely

    Excel::fake();

    $this->get(route('admin.ujian.leaderboard.export', $ujian))->assertOk();

    Excel::assertDownloaded("Leaderboard-{$ujian->nama}.xlsx", function (LeaderboardExport $export) use ($tinggi) {
        $rows = $export->collection();

        return $rows->count() === 2 && $rows->first()->id === $tinggi->id;
    });
});
