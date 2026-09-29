<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * config/app.php's timezone moved from a hardcoded UTC to Asia/Jakarta (WIB,
 * UTC+7, no DST). Everything the app stamped with now() until then —
 * created_at/updated_at, a peserta's waktu_mulai/waktu_selesai — was stored
 * as a UTC wall-clock value, so it's shifted +7h here to keep reading right.
 *
 * Deliberately NOT shifted:
 *  - ujian.sesi_mulai / sesi_selesai: typed by admins, already meant as WIB
 *    (that mismatch is exactly the bug the timezone change fixes).
 *  - failed_jobs.failed_at: filled by the database's own CURRENT_TIMESTAMP,
 *    i.e. the DB server's zone, not the app's.
 *  - sessions / cache / jobs: unix-epoch integers, zone-independent.
 */
return new class extends Migration
{
    private const JAM = 7;

    public function up(): void
    {
        $this->geser(self::JAM);
    }

    public function down(): void
    {
        $this->geser(-self::JAM);
    }

    /**
     * @return array<string, list<string>>
     */
    private function kolom(): array
    {
        $permission = config('permission.table_names');
        $timestamps = ['created_at', 'updated_at'];

        return [
            'users' => ['email_verified_at', ...$timestamps],
            'password_reset_tokens' => ['created_at'],
            'jenjang' => $timestamps,
            'pelajaran' => $timestamps,
            'bank_soal' => $timestamps,
            'soal' => $timestamps,
            'ujian' => $timestamps,
            'peserta' => $timestamps,
            'peserta_ujian' => ['waktu_mulai', 'waktu_selesai', ...$timestamps],
            'peserta_soal' => $timestamps,
            'peserta_pelajaran' => $timestamps,
            $permission['roles'] ?? 'roles' => $timestamps,
            $permission['permissions'] ?? 'permissions' => $timestamps,
        ];
    }

    private function geser(int $jam): void
    {
        foreach ($this->kolom() as $tabel => $kolom) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            // NULL stays NULL in every driver's date arithmetic, so no
            // whereNotNull() needed — and one UPDATE per table keeps a row's
            // columns consistent with each other.
            DB::table($tabel)->update(
                collect($kolom)
                    ->filter(fn (string $k) => Schema::hasColumn($tabel, $k))
                    ->mapWithKeys(fn (string $k) => [$k => DB::raw($this->tambahJam($k, $jam))])
                    ->all()
            );
        }
    }

    private function tambahJam(string $kolom, int $jam): string
    {
        $k = DB::getQueryGrammar()->wrap($kolom);

        return match (DB::getDriverName()) {
            'sqlite' => "datetime({$k}, '".sprintf('%+d', $jam)." hours')",
            'pgsql' => "{$k} + interval '{$jam} hours'",
            'sqlsrv' => "DATEADD(hour, {$jam}, {$k})",
            default => "DATE_ADD({$k}, INTERVAL {$jam} HOUR)",
        };
    }
};
