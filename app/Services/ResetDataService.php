<?php

namespace App\Services;

use App\Enums\KategoriReset;
use App\Exports\BackupSebelumResetExport;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Bulk-wipes whole categories of olimpiade data (see App\Enums\KategoriReset)
 * — the engine behind Pengaturan → Reset Data and `olimpiade:reset-data`.
 * Callers are responsible for authorization and confirmation; this class only
 * guarantees the wipe itself is safe: dependencies resolved, nobody mid-exam,
 * a backup written first, and the delete all-or-nothing.
 */
class ResetDataService
{
    /** Backups live here (private `local` disk) and are kept forever. */
    public const FOLDER_BACKUP = 'backups';

    /**
     * Row count per table that jalankan() would delete, in delete order.
     *
     * @param  array<int, KategoriReset|string>  $kategori
     * @return array<string, int>
     */
    public function ringkasan(array $kategori): array
    {
        return collect(KategoriReset::lengkapi($kategori))
            ->flatMap(fn (KategoriReset $k) => $k->tabel())
            ->mapWithKeys(fn (string $tabel) => [$tabel => DB::table($tabel)->count()])
            ->all();
    }

    /**
     * Every category wipes Hasil Ujian (directly or via a dependency), so
     * this always applies: refuse while any ujian session window is open, or
     * any peserta is mid-attempt — their exam page would start erroring out
     * from under them.
     */
    public function alasanDitolak(): ?string
    {
        if (Ujian::query()->where('sesi_mulai', '<=', now())->where('sesi_selesai', '>=', now())->exists()) {
            return 'Ada ujian yang sesinya sedang berlangsung. Tunggu sampai sesi selesai.';
        }

        $sedangMengerjakan = PesertaUjian::query()
            ->whereNotNull('waktu_mulai')
            ->whereNull('waktu_selesai')
            ->with('ujian')
            ->get()
            ->contains(fn (PesertaUjian $attempt) => ! $attempt->waktuHabis());

        return $sedangMengerjakan ? 'Masih ada peserta yang sedang mengerjakan ujian.' : null;
    }

    /**
     * Backup → delete (one transaction) → remove files → restart IDs → log.
     * Serialized by a cache lock so two admins can't run it at once.
     *
     * @param  array<int, KategoriReset|string>  $kategori
     * @return array{backup: string, terhapus: array<string, int>}
     *
     * @throws ValidationException When nothing valid was chosen, it's refused
     *                             (see alasanDitolak()), or another reset is running.
     */
    public function jalankan(array $kategori, ?User $oleh = null): array
    {
        $kategori = KategoriReset::lengkapi($kategori);

        if ($kategori === []) {
            throw ValidationException::withMessages(['dipilih' => 'Pilih minimal satu data yang akan dihapus.']);
        }

        $lock = Cache::lock('reset-data', 600);

        if (! $lock->get()) {
            throw ValidationException::withMessages(['dipilih' => 'Reset data lain sedang berjalan. Coba lagi sebentar.']);
        }

        try {
            if ($alasan = $this->alasanDitolak()) {
                throw ValidationException::withMessages(['dipilih' => $alasan]);
            }

            // Written before anything is touched: if the backup fails, the
            // exception stops us here with every row still in place.
            $backup = self::FOLDER_BACKUP.'/reset-'.now()->format('Ymd_His').'.xlsx';
            Excel::store(new BackupSebelumResetExport($kategori), $backup, 'local');

            // delete(), not truncate(): on MySQL, TRUNCATE implicitly commits
            // (no rollback) and refuses tables referenced by a foreign key.
            $terhapus = DB::transaction(function () use ($kategori): array {
                $hasil = [];

                foreach ($kategori as $k) {
                    foreach ($k->tabel() as $tabel) {
                        $hasil[$tabel] = DB::table($tabel)->delete();
                    }
                }

                return $hasil;
            });

            // Only reached once the transaction has committed. Both steps are
            // deliberately outside it: files can't roll back anyway, and
            // MySQL's ALTER TABLE would implicitly commit it.
            foreach ($kategori as $k) {
                foreach ($k->folder() as ['disk' => $disk, 'path' => $path]) {
                    Storage::disk($disk)->deleteDirectory($path);
                }
            }

            $this->mulaiUlangId(array_keys($terhapus));

            Log::warning('Reset data dijalankan', [
                'oleh' => $oleh ? "{$oleh->id}:{$oleh->username}" : 'console',
                'kategori' => array_map(fn (KategoriReset $k) => $k->value, $kategori),
                'terhapus' => $terhapus,
                'backup' => $backup,
            ]);

            return ['backup' => $backup, 'terhapus' => $terhapus];
        } finally {
            $lock->release();
        }
    }

    /**
     * Backups on disk, newest first.
     *
     * @return list<array{nama: string, ukuran: int, waktu: int}>
     */
    public function daftarBackup(): array
    {
        $disk = Storage::disk('local');

        return collect($disk->files(self::FOLDER_BACKUP))
            ->filter(fn (string $path) => str_ends_with($path, '.xlsx'))
            ->map(fn (string $path) => [
                'nama' => basename($path),
                'ukuran' => $disk->size($path),
                'waktu' => $disk->lastModified($path),
            ])
            ->sortByDesc('nama')
            ->values()
            ->all();
    }

    /**
     * Restart auto-increment IDs at 1 for tables that are now empty (a
     * table can still have rows if something was inserted meanwhile — then
     * it's left alone rather than risking an ID collision).
     *
     * @param  list<string>  $tabel
     */
    private function mulaiUlangId(array $tabel): void
    {
        $prefix = DB::getTablePrefix();

        foreach ($tabel as $t) {
            if (DB::table($t)->exists()) {
                continue;
            }

            match (DB::getDriverName()) {
                'mysql', 'mariadb' => DB::statement("ALTER TABLE `{$prefix}{$t}` AUTO_INCREMENT = 1"),
                'sqlite' => DB::table('sqlite_sequence')->where('name', $prefix.$t)->delete(),
                'pgsql' => DB::statement("ALTER SEQUENCE \"{$prefix}{$t}_id_seq\" RESTART WITH 1"),
                default => null,
            };
        }
    }
}
