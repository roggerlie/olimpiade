<?php

namespace App\Policies;

use App\Models\SiswaUjian;
use App\Models\User;

/**
 * Guards the CBT routes (routes/cbt.php): a student may only view, answer,
 * or submit their own attempt — never another student's by editing the URL.
 * Admin routes query SiswaUjian directly and don't go through this policy.
 */
class SiswaUjianPolicy
{
    public function view(User $user, SiswaUjian $siswaUjian): bool
    {
        return $siswaUjian->siswa->user_id === $user->id;
    }
}
