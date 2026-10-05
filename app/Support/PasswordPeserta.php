<?php

namespace App\Support;

/**
 * The one place peserta passwords are generated — import, admin create,
 * "Reset Password" and "Acak Ulang Password" all go through here.
 *
 * Six uppercase letters/digits (e.g. "7F8PV3"), deliberately leaving out
 * look-alike characters (0/O, 1/I/L, 5/S, 8/B, 2/Z): these passwords are
 * handed out on paper (kartu peserta, the peserta Excel export) and typed
 * back in by students, so nothing on the sheet should be ambiguous.
 */
class PasswordPeserta
{
    public const PANJANG = 6;

    public const KARAKTER = 'ACDEFGHJKMNPQRTUVWXY34679';

    public static function generate(): string
    {
        $password = '';
        $batas = strlen(self::KARAKTER) - 1;

        for ($i = 0; $i < self::PANJANG; $i++) {
            $password .= self::KARAKTER[random_int(0, $batas)];
        }

        return $password;
    }
}
