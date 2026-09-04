<?php

namespace App\Policies;

use App\Models\PesertaUjian;
use App\Models\User;

/**
 * Guards the CBT routes (routes/cbt.php): a student may only view, answer,
 * or submit their own attempt — never another student's by editing the URL.
 * Admin routes query PesertaUjian directly and don't go through this policy.
 */
class PesertaUjianPolicy
{
    public function view(User $user, PesertaUjian $pesertaUjian): bool
    {
        return $pesertaUjian->peserta->user_id === $user->id;
    }
}
