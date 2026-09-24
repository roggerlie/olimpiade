<?php

namespace App\Policies;

use App\Models\Peserta;
use App\Models\PesertaUjian;

/**
 * Guards the CBT routes (routes/cbt.php): a student may only view, answer,
 * or submit their own attempt — never another student's by editing the URL.
 * Admin routes query PesertaUjian directly and don't go through this policy.
 *
 * Checked explicitly against the `peserta` guard's user (via Gate::forUser
 * in Cbt\ExamController / Cbt\AnswerController) rather than the default
 * `web` guard Gate::authorize() would otherwise resolve.
 */
class PesertaUjianPolicy
{
    public function view(Peserta $peserta, PesertaUjian $pesertaUjian): bool
    {
        return $pesertaUjian->peserta_id === $peserta->id;
    }
}
