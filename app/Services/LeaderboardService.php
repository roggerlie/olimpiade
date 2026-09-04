<?php

namespace App\Services;

use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Database\Eloquent\Collection;

/**
 * Ranks one ujian's finished attempts: highest nilai first, ties broken by
 * fastest durasiPengerjaan. Only submitted attempts are ranked — students
 * still mid-exam or never started aren't placed. Shared by the admin
 * leaderboard page and its Excel export so both always agree on order.
 */
class LeaderboardService
{
    public function ranking(Ujian $ujian): Collection
    {
        return PesertaUjian::query()
            ->where('ujian_id', $ujian->id)
            ->whereNotNull('waktu_selesai')
            ->with('peserta')
            ->get()
            ->sort(function (PesertaUjian $a, PesertaUjian $b) {
                $nilaiA = (float) $a->nilai;
                $nilaiB = (float) $b->nilai;

                return $nilaiA === $nilaiB
                    ? $a->durasiPengerjaan() <=> $b->durasiPengerjaan()
                    : $nilaiB <=> $nilaiA;
            })
            ->values();
    }
}
