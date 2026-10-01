<?php

namespace App\Services;

use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use Illuminate\Support\Facades\DB;

/**
 * Starts an exam attempt: draws a random subset of the bank's soal (sized to
 * ujian.jumlah_soal), freezes their order into peserta_soal, and stamps
 * waktu_mulai. This is the one and only place question order gets rolled —
 * idempotent by design, so a page refresh or a repeat "Mulai" click never
 * re-shuffles or grants extra time.
 */
class ExamAttemptService
{
    public function mulai(PesertaUjian $pesertaUjian): void
    {
        if ($pesertaUjian->waktu_mulai !== null) {
            return;
        }

        DB::transaction(function () use ($pesertaUjian): void {
            // Two near-simultaneous "Mulai" requests (double click, two tabs)
            // can both get past the cheap check above with waktu_mulai still
            // null in memory. Re-read the row under a lock so the second one
            // waits for the first to commit, then sees it already started —
            // instead of drawing a second set and hitting the unique
            // (peserta_ujian_id, urutan) index with a 500.
            $terkunci = PesertaUjian::query()->whereKey($pesertaUjian->id)->lockForUpdate()->first();

            if ($terkunci->waktu_mulai !== null) {
                return;
            }

            $ujian = $pesertaUjian->ujian;

            $soalIds = Soal::query()
                ->where('bank_soal_id', $ujian->bank_soal_id)
                ->inRandomOrder()
                ->limit($ujian->jumlah_soal)
                ->pluck('id');

            $urutan = 1;

            foreach ($soalIds as $soalId) {
                PesertaSoal::create([
                    'peserta_ujian_id' => $pesertaUjian->id,
                    'soal_id' => $soalId,
                    'urutan' => $urutan++,
                ]);
            }

            $terkunci->update(['waktu_mulai' => now()]);
        });

        // Either way the caller's copy now matches the database.
        $pesertaUjian->refresh();
    }
}
