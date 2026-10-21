<?php

namespace App\Services;

use App\Models\PesertaUjian;
use App\Models\User;
use App\Support\LogAktivitas;

/**
 * Finalizes an attempt: tallies benar/salah against each soal's real
 * jawaban and stamps waktu_selesai. Scoring is benar*9 - salah with no
 * floor at zero — ported as-is from the original system, unanswered soal
 * count as neither benar nor salah.
 *
 * Shared by the student's own "Selesaikan", the exam page noticing time ran
 * out, and admin/operator's "Selesaikan" on the Peserta Ujian page (there's
 * no scheduler — an expired attempt left open stays open until one of those
 * happens), so every path closes out an attempt identically.
 */
class ScoringService
{
    /**
     * @param  bool  $otomatis  True when closed out because time ran out
     *                          (not by the student's own "Selesaikan").
     * @param  User|null  $oleh  The admin-tier user who closed it out on
     *                           the student's behalf, if any.
     */
    public function submit(PesertaUjian $pesertaUjian, bool $otomatis = false, ?User $oleh = null): void
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

        LogAktivitas::catat(
            LogAktivitas::UJIAN,
            'submit_ujian',
            match (true) {
                $oleh !== null => "Ujian {$pesertaUjian->peserta->nama} diselesaikan oleh {$oleh->name}",
                $otomatis => 'Ujian diselesaikan otomatis (waktu habis)',
                default => 'Ujian diselesaikan',
            },
            // The peserta stays the causer so the Ujian log reads per peserta;
            // whoever pressed the button is recorded in `oleh`.
            $pesertaUjian->peserta,
            $pesertaUjian,
            array_filter([
                'ujian' => $pesertaUjian->ujian->nama,
                'otomatis' => $otomatis,
                'nilai' => $pesertaUjian->nilai,
                'oleh' => $oleh ? LogAktivitas::namaPelaku($oleh) : null,
            ], fn ($nilai) => $nilai !== null),
        );
    }
}
