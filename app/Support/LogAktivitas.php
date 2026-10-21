<?php

namespace App\Support;

use App\Models\Peserta;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * The one way this app writes to the activity log (spatie/laravel-activitylog,
 * table `activity_log`), so every entry carries the same log names and the
 * same request context. Model create/update/delete is logged on its own by
 * App\Models\Concerns\CatatAktivitas; everything else — logins, exam
 * attempts, bulk admin actions that bypass model events — goes through here.
 *
 * Shown on Sistem → Log Aktivitas (permission `log-aktivitas.view`).
 */
final class LogAktivitas
{
    /** Login, logout, failed and throttled logins, for both guards. */
    public const AUTH = 'auth';

    /** What admin-tier users changed in /admin. */
    public const ADMIN = 'admin';

    /** Peserta starting and submitting exam attempts. */
    public const UJIAN = 'ujian';

    /** @var array<string, string> */
    public const LOG = [
        self::AUTH => 'Login',
        self::ADMIN => 'Aktivitas Admin',
        self::UJIAN => 'Ujian',
    ];

    /**
     * Every `event` written per log, with its label — the Log Aktivitas
     * page's event filter.
     *
     * @var array<string, array<string, string>>
     */
    public const EVENT = [
        self::AUTH => [
            'login' => 'Login',
            'logout' => 'Logout',
            'login_gagal' => 'Login Gagal',
            'login_diblokir' => 'Login Diblokir',
        ],
        self::ADMIN => [
            'created' => 'Tambah Data',
            'updated' => 'Ubah Data',
            'deleted' => 'Hapus Data',
            'import_peserta' => 'Import Peserta',
            'import_soal' => 'Import Soal',
            'daftar_peserta' => 'Daftarkan Peserta Ujian',
            'batal_daftar_peserta' => 'Batal Daftar Peserta Ujian',
            'pindah_ruangan' => 'Pindah Ruangan',
            'bagi_ruangan' => 'Bagi Ruangan Otomatis',
            'reset_progres' => 'Reset Progres Ujian',
            'ubah_role' => 'Ubah Role Pengguna',
            'ubah_peran' => 'Ubah Hak Akses Peran',
            'ubah_pengaturan' => 'Ubah Pengaturan',
            'reset_data' => 'Reset Data',
            'hapus_log' => 'Hapus Log Lama',
        ],
        self::UJIAN => [
            'mulai_ujian' => 'Mulai Ujian',
            'submit_ujian' => 'Selesai Ujian',
        ],
    ];

    /** Readable reasons for a `login_gagal` entry's `alasan`. */
    public const ALASAN_GAGAL = [
        'akun_tidak_ditemukan' => 'Akun tidak ditemukan',
        'password_salah' => 'Password salah',
        'tidak_punya_akses' => 'Tidak punya akses admin',
    ];

    /**
     * The Log Aktivitas page's query-string filters (log, dari, sampai,
     * event, q) as cari() arguments. All of it is user input, so
     * anything unrecognised is dropped rather than trusted.
     *
     * @param  array<string, mixed>  $input
     * @return array{log: string, dari: ?string, sampai: ?string, event: ?string, kataKunci: ?string}
     */
    public static function filter(array $input): array
    {
        $teks = fn (string $kunci) => is_string($input[$kunci] ?? null) ? trim($input[$kunci]) : '';
        $tanggal = fn (string $kunci) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $teks($kunci)) && strtotime($teks($kunci)) ? $teks($kunci) : null;

        $log = array_key_exists($teks('log'), self::LOG) ? $teks('log') : self::AUTH;

        return [
            'log' => $log,
            'dari' => $tanggal('dari'),
            'sampai' => $tanggal('sampai'),
            'event' => array_key_exists($teks('event'), self::EVENT[$log]) ? $teks('event') : null,
            'kataKunci' => $teks('q') ?: null,
        ];
    }

    /**
     * One log's entries, newest first, narrowed by the Log Aktivitas page's
     * filters — shared by the page and its Excel export so both always show
     * the same rows. $dari/$sampai are Y-m-d dates, both inclusive.
     *
     * @return Builder<Activity>
     */
    public static function cari(
        string $log,
        ?string $dari = null,
        ?string $sampai = null,
        ?string $event = null,
        ?string $kataKunci = null,
    ): Builder {
        return Activity::query()
            ->with('causer')
            ->where('log_name', $log)
            ->when($dari, fn (Builder $query, string $dari) => $query->where('created_at', '>=', Carbon::parse($dari)->startOfDay()))
            ->when($sampai, fn (Builder $query, string $sampai) => $query->where('created_at', '<=', Carbon::parse($sampai)->endOfDay()))
            ->when($event, fn (Builder $query, string $event) => $query->where('event', $event))
            ->when($kataKunci, function (Builder $query, string $kataKunci): void {
                $query->where(function (Builder $q) use ($kataKunci): void {
                    $q->where('description', 'like', "%{$kataKunci}%")
                        ->orWhere('properties->pelaku', 'like', "%{$kataKunci}%")
                        ->orWhere('properties->username', 'like', "%{$kataKunci}%")
                        ->orWhere('properties->label', 'like', "%{$kataKunci}%")
                        ->orWhere('properties->ip', 'like', "%{$kataKunci}%");
                });
            })
            ->latest()
            ->latest('id');
    }

    /**
     * Entries past the retention (activitylog.delete_records_older_than_days),
     * across every log — what Log Aktivitas's "Hapus Log Lama" removes.
     *
     * @return Builder<Activity>
     */
    public static function logLama(): Builder
    {
        return Activity::query()->where('created_at', '<', now()->subDays((int) config('activitylog.delete_records_older_than_days'))->startOfDay());
    }

    public static function labelEvent(Activity $activity): string
    {
        return self::EVENT[$activity->log_name][$activity->event] ?? (string) $activity->event;
    }

    /** x-ui.badge color for an entry's event. */
    public static function warnaEvent(Activity $activity): string
    {
        return match ($activity->event) {
            'login', 'created', 'mulai_ujian', 'submit_ujian' => 'success',
            'login_gagal', 'deleted', 'reset_data', 'hapus_log' => 'error',
            'login_diblokir', 'reset_progres', 'ubah_role', 'ubah_peran' => 'warning',
            'logout' => 'light',
            default => 'primary',
        };
    }

    /**
     * One-line summary for the log table / export: who was targeted and
     * what changed, depending on the kind of entry.
     */
    public static function keterangan(Activity $activity): string
    {
        $p = $activity->properties;

        return match (true) {
            $activity->log_name === self::AUTH => collect([
                ($p['guard'] ?? null) === 'peserta' ? 'Peserta' : 'Admin',
                ($p['metode'] ?? null) === 'google' ? 'via Google' : null,
                in_array($activity->event, ['login_gagal', 'login_diblokir'], true) ? 'akun: '.($p['username'] ?? '—') : null,
                self::ALASAN_GAGAL[$p['alasan'] ?? ''] ?? null,
            ])->filter()->join(' · '),
            $activity->log_name === self::UJIAN => collect([
                $p['ujian'] ?? null,
                isset($p['nilai']) ? "nilai {$p['nilai']}" : null,
            ])->filter()->join(' · '),
            default => collect([
                $p['label'] ?? null,
                $activity->event === 'updated' && ! empty($p['attributes']) ? 'ubah: '.implode(', ', array_keys($p['attributes'])) : null,
            ])->filter()->join(' · '),
        };
    }

    /**
     * Who did it: the causer if it still exists, else the name snapshot
     * taken when the entry was written (see catat()).
     */
    public static function pelakuDari(Activity $activity): string
    {
        return $activity->causer
            ? self::namaPelaku($activity->causer)
            : ($activity->properties['pelaku'] ?? '—');
    }

    /**
     * @param  Model|null  $oleh  Who did it. Null on purpose means "nobody
     *                            known" (e.g. a failed login, a console
     *                            command) — never falls back to whoever
     *                            happens to be logged in on the `web` guard.
     * @param  array<string, mixed>  $properti
     */
    public static function catat(
        string $log,
        string $event,
        string $deskripsi,
        ?Model $oleh = null,
        ?Model $subjek = null,
        array $properti = [],
    ): void {
        $logger = activity($log)->event($event);

        $oleh ? $logger->causedBy($oleh) : $logger->causedByAnonymous();

        if ($subjek) {
            $logger->performedOn($subjek);
        }

        // `pelaku` is a snapshot: the causer row may be gone later (a peserta
        // wiped by Reset Data), and the entry should still say who it was.
        $logger->withProperties($properti + self::konteksRequest() + ['pelaku' => $oleh ? self::namaPelaku($oleh) : null])
            ->log($deskripsi);
    }

    /**
     * Shorthand for an admin action done by the current `web` user.
     *
     * @param  array<string, mixed>  $properti
     */
    public static function admin(string $event, string $deskripsi, ?Model $subjek = null, array $properti = []): void
    {
        self::catat(self::ADMIN, $event, $deskripsi, auth('web')->user(), $subjek, $properti);
    }

    /**
     * IP and user agent of the current HTTP request, or nothing at all when
     * running from the console (scheduler, artisan).
     *
     * @return array{ip?: string|null, user_agent?: string|null}
     */
    public static function konteksRequest(): array
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return [];
        }

        return ['ip' => request()->ip(), 'user_agent' => request()->userAgent()];
    }

    /**
     * Display name of whoever caused an entry, for the log page / export.
     */
    public static function namaPelaku(?Model $pelaku): string
    {
        return match (true) {
            $pelaku instanceof User => "{$pelaku->name} ({$pelaku->username})",
            $pelaku instanceof Peserta => "{$pelaku->nama} ({$pelaku->noreg})",
            default => '—',
        };
    }
}
