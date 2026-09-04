<?php

namespace App\Services;

use App\Models\SiswaSoal;
use App\Models\SiswaUjian;
use App\Models\Soal;
use Illuminate\Support\Facades\DB;

/**
 * Starts an exam attempt: draws a random subset of the bank's soal (sized to
 * ujian.jumlah_soal), freezes their order into siswa_soal, and stamps
 * waktu_mulai. This is the one and only place question order gets rolled —
 * idempotent by design, so a page refresh or a repeat "Mulai" click never
 * re-shuffles or grants extra time.
 */
class ExamAttemptService
{
    public function mulai(SiswaUjian $siswaUjian): void
    {
        if ($siswaUjian->waktu_mulai !== null) {
            return;
        }

        $ujian = $siswaUjian->ujian;

        $soalIds = Soal::query()
            ->where('bank_soal_id', $ujian->bank_soal_id)
            ->inRandomOrder()
            ->limit($ujian->jumlah_soal)
            ->pluck('id');

        DB::transaction(function () use ($siswaUjian, $soalIds): void {
            $urutan = 1;

            foreach ($soalIds as $soalId) {
                SiswaSoal::create([
                    'siswa_ujian_id' => $siswaUjian->id,
                    'soal_id' => $soalId,
                    'urutan' => $urutan++,
                ]);
            }

            $siswaUjian->update(['waktu_mulai' => now()]);
        });
    }
}
