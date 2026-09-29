<?php

use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('a sesi typed in WIB is open during those WIB hours', function () {
    $ujian = Ujian::factory()->create(['sesi_mulai' => '2026-11-17 08:15', 'sesi_selesai' => '2026-11-17 10:15']);

    $this->travelTo(Carbon::parse('2026-11-17 08:30', 'Asia/Jakarta'));
    expect($ujian->fresh()->sesiSedangBerlangsung())->toBeTrue();

    // 08:30 UTC is 15:30 WIB — long after the sesi closed.
    $this->travelTo(Carbon::parse('2026-11-17 08:30', 'UTC'));
    expect($ujian->fresh()->sesiSedangBerlangsung())->toBeFalse();
});

test('the migration shifts app-recorded times by 7 hours but leaves sesi times alone', function () {
    $attempt = PesertaUjian::factory()->create();
    DB::table('peserta_ujian')->where('id', $attempt->id)->update([
        'waktu_mulai' => '2026-09-29 01:00:00',
        'waktu_selesai' => null,
        'created_at' => '2026-09-29 01:00:00',
    ]);
    DB::table('ujian')->where('id', $attempt->ujian_id)->update(['sesi_mulai' => '2026-11-17 08:15:00']);

    // RefreshDatabase already ran it on empty tables; re-run it on these rows.
    (require database_path('migrations/2026_09_29_151659_shift_utc_recorded_timestamps_to_wib.php'))->up();

    $baris = DB::table('peserta_ujian')->where('id', $attempt->id)->first();
    expect(Carbon::parse($baris->waktu_mulai)->format('Y-m-d H:i'))->toBe('2026-09-29 08:00')
        ->and($baris->waktu_selesai)->toBeNull()
        ->and(Carbon::parse($baris->created_at)->format('Y-m-d H:i'))->toBe('2026-09-29 08:00')
        ->and(Carbon::parse(DB::table('ujian')->where('id', $attempt->ujian_id)->value('sesi_mulai'))->format('Y-m-d H:i'))->toBe('2026-11-17 08:15');
});
