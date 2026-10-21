<?php

namespace App\Services;

use App\Models\Peserta;
use App\Support\LogAktivitas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Flags suspicious peserta logins ("joki") from the `auth` activity log,
 * over a date range — the Deteksi Login Ganda tab of Log Aktivitas.
 *
 * Only a hint for the panitia to look into, never proof: a peserta may
 * legitimately switch PC after one breaks, and a school lab behind one
 * public IP makes every PC in it look alike.
 */
class DeteksiLoginGandaService
{
    /**
     * Peserta who logged in from 2+ different IPs or devices (user agents).
     *
     * @return Collection<int, array{peserta: ?Peserta, pelaku: string, jumlahLogin: int, ip: list<string>, perangkat: list<string>, terakhir: Carbon}>
     */
    public function pesertaBanyakPerangkat(string $dari, string $sampai): Collection
    {
        $hasil = $this->loginPeserta($dari, $sampai)
            ->groupBy('causer_id')
            ->map(fn (Collection $login) => [
                'peserta' => $login->first()->causer,
                'pelaku' => LogAktivitas::pelakuDari($login->first()),
                'jumlahLogin' => $login->count(),
                'ip' => $this->unik($login, 'ip'),
                'perangkat' => $this->unik($login, 'user_agent'),
                'terakhir' => $login->max('created_at'),
            ])
            ->filter(fn (array $baris) => count($baris['ip']) > 1 || count($baris['perangkat']) > 1);

        return $hasil
            ->sortByDesc(fn (array $baris) => [count($baris['ip']) + count($baris['perangkat']), $baris['terakhir']])
            ->values();
    }

    /**
     * Same IP + same device (user agent) used to log in as 2+ different peserta.
     *
     * @return Collection<int, array{ip: string, perangkat: string, pelaku: list<string>, jumlahLogin: int, terakhir: Carbon}>
     */
    public function perangkatBanyakPeserta(string $dari, string $sampai): Collection
    {
        return $this->loginPeserta($dari, $sampai)
            ->groupBy(fn (Activity $login) => ($login->properties['ip'] ?? '').'|'.($login->properties['user_agent'] ?? ''))
            ->map(fn (Collection $login) => [
                'ip' => (string) ($login->first()->properties['ip'] ?? '—'),
                'perangkat' => (string) ($login->first()->properties['user_agent'] ?? '—'),
                'pelaku' => $login->unique('causer_id')->map(fn (Activity $a) => LogAktivitas::pelakuDari($a))->sort()->values()->all(),
                'jumlahLogin' => $login->count(),
                'terakhir' => $login->max('created_at'),
            ])
            ->filter(fn (array $baris) => count($baris['pelaku']) > 1)
            ->sortByDesc(fn (array $baris) => count($baris['pelaku']))
            ->values();
    }

    /**
     * @return Collection<int, Activity>
     */
    private function loginPeserta(string $dari, string $sampai): Collection
    {
        return LogAktivitas::cari(LogAktivitas::AUTH, $dari, $sampai, 'login')
            ->where('causer_type', (new Peserta)->getMorphClass())
            ->whereNotNull('causer_id')
            ->get();
    }

    /**
     * @param  Collection<int, Activity>  $login
     * @return list<string>
     */
    private function unik(Collection $login, string $kunci): array
    {
        return $login->map(fn (Activity $a) => $a->properties[$kunci] ?? null)->filter()->unique()->sort()->values()->all();
    }
}
