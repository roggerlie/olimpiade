<?php

namespace App\Services;

use App\Models\SiswaUjian;

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
    public function submit(SiswaUjian $siswaUjian): void
    {
        if ($siswaUjian->sudahSubmit()) {
            return;
        }

        $benar = 0;
        $salah = 0;

        foreach ($siswaUjian->siswaSoal()->with('soal:id,jawaban')->get() as $siswaSoal) {
            if ($siswaSoal->jawaban === null) {
                continue;
            }

            if ($siswaSoal->jawaban === $siswaSoal->soal->jawaban) {
                $benar++;
            } else {
                $salah++;
            }
        }

        $siswaUjian->update([
            'waktu_selesai' => now(),
            'benar' => $benar,
            'salah' => $salah,
            'nilai' => $benar * 9 - $salah,
        ]);
    }
}
