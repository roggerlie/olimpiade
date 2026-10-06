<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Admin-editable settings, one row per `kunci` (Pengaturan page, administrator
 * only). Read with ambil(), written with simpan() — a blank value deletes the
 * row so ambil() falls back to its default again.
 */
#[Table(name: 'pengaturan')]
#[Fillable(['kunci', 'nilai'])]
class Pengaturan extends Model
{
    public const KETUA_PELAKSANA = 'kartu.ketua_pelaksana';

    /** Path on the `public` disk of an uploaded replacement QR. */
    public const QR_KETUA = 'kartu.qr_ketua';

    /** Shipped default for QR_KETUA (public/), used until one is uploaded. */
    public const QR_KETUA_BAWAAN = 'images/pbsf/qr-ketua-pelaksana.png';

    public static function ambil(string $kunci, ?string $default = null): ?string
    {
        return static::query()->where('kunci', $kunci)->value('nilai') ?? $default;
    }

    public static function simpan(string $kunci, ?string $nilai): void
    {
        if ($nilai === null || trim($nilai) === '') {
            static::query()->where('kunci', $kunci)->delete();

            return;
        }

        static::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => trim($nilai)]);
    }

    /**
     * URL of the Ketua Pelaksana's QR for the kartu peserta: the uploaded one
     * if any (and still on disk), else the shipped default.
     */
    public static function urlQrKetua(): string
    {
        $path = static::ambil(self::QR_KETUA);

        return $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : asset(self::QR_KETUA_BAWAAN);
    }
}
