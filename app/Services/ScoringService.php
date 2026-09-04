<?php

namespace App\Services;

use App\Models\PesertaUjian;

/**
 * Finalizes an attempt: tallies benar/salah against each soal's real
 * jawaban and stamps waktu_selesai. Scoring is benar*9 - salah with no
 * floor at zero — ported as-is from the original system, unanswered soal
 * count as neither benar nor salah.
 *
 * Shared by the student's own "Submit" action and the exam:auto-submit
 * scheduled command, so both close out an attempt identically.
 */
class ScoringService
{
    public function submit(PesertaUjian $pesertaUjian): void
    {
        if ($pesertaUjian->sudahSubmit()) {
            return;
        }

        $benar = 0;
        $salah = 0;

        foreach ($pesertaUjian->pesertaSoal()->with('soal:id,jawaban')->get() as $pesertaSoal) {
            if ($pesertaSoal->jawaban === null) {
                continue;
            }

            if ($pesertaSoal->jawaban === $pesertaSoal->soal->jawaban) {
                $benar++;
            } else {
                $salah++;
            }
        }

        $pesertaUjian->update([
            'waktu_selesai' => now(),
            'benar' => $benar,
            'salah' => $salah,
            'nilai' => $benar * 9 - $salah,
        ]);
    }
}
