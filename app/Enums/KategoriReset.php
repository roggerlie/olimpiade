<?php

namespace App\Enums;

/**
 * What the Pengaturan → Reset Data page (and `olimpiade:reset-data`) can
 * wipe, and — the single source of truth for it — what each one drags along.
 * The foreign keys are mostly `restrictOnDelete` (see the create_* migrations),
 * so a category can't be deleted without everything that points at it:
 *
 *   jenjang, pelajaran ← bank_soal ← soal ← peserta_soal
 *                        bank_soal ← ujian ← peserta_ujian ← peserta_soal
 *   jenjang ← peserta ← peserta_ujian, peserta_pelajaran
 *
 * `users`, roles and permissions are never part of any category.
 */
enum KategoriReset: string
{
    case HasilUjian = 'hasil-ujian';
    case Peserta = 'peserta';
    case Ujian = 'ujian';
    case BankSoal = 'bank-soal';
    case MasterData = 'master-data';

    public function label(): string
    {
        return match ($this) {
            self::HasilUjian => 'Hasil Ujian',
            self::Peserta => 'Peserta',
            self::Ujian => 'Ujian',
            self::BankSoal => 'Bank Soal',
            self::MasterData => 'Master Data',
        };
    }

    public function deskripsi(): string
    {
        return match ($this) {
            self::HasilUjian => 'Progres, jawaban, dan nilai semua peserta di semua ujian.',
            self::Peserta => 'Akun peserta beserta minat lomba dan arsip file import peserta.',
            self::Ujian => 'Semua jadwal ujian.',
            self::BankSoal => 'Semua bank soal, soal, gambar soal, dan arsip file import soal.',
            self::MasterData => 'Jenjang dan Pelajaran.',
        };
    }

    /**
     * Categories that must be deleted along with this one (direct only —
     * see lengkapi() for the transitive closure).
     *
     * @return list<self>
     */
    public function dependensi(): array
    {
        return match ($this) {
            self::HasilUjian => [],
            self::Peserta, self::Ujian => [self::HasilUjian],
            self::BankSoal => [self::Ujian],
            self::MasterData => [self::BankSoal, self::Peserta],
        };
    }

    /**
     * Tables this category owns, child-first so no restrict FK is hit.
     *
     * @return list<string>
     */
    public function tabel(): array
    {
        return match ($this) {
            self::HasilUjian => ['peserta_soal', 'peserta_ujian'],
            self::Peserta => ['peserta_pelajaran', 'peserta'],
            self::Ujian => ['ujian'],
            self::BankSoal => ['soal', 'bank_soal'],
            self::MasterData => ['pelajaran', 'jenjang'],
        };
    }

    /**
     * Storage directories whose files belong to this category's rows.
     *
     * @return list<array{disk: string, path: string}>
     */
    public function folder(): array
    {
        return match ($this) {
            self::Peserta => [['disk' => 'local', 'path' => 'imports/peserta']],
            self::BankSoal => [['disk' => 'public', 'path' => 'soal'], ['disk' => 'local', 'path' => 'imports/soal']],
            default => [],
        };
    }

    /**
     * The given categories plus everything they depend on, in safe delete
     * order (the order the cases are declared in). Accepts raw values too,
     * silently dropping unknown ones — they arrive from a public Livewire
     * property / CLI option, so they can't be trusted to be valid.
     *
     * @param  array<int, self|string>  $kategori
     * @return list<self>
     */
    public static function lengkapi(array $kategori): array
    {
        $antrian = collect($kategori)
            ->map(fn (self|string $k) => $k instanceof self ? $k : self::tryFrom($k))
            ->filter()
            ->values()
            ->all();

        $hasil = [];

        while ($antrian !== []) {
            $k = array_pop($antrian);

            if (! in_array($k, $hasil, true)) {
                $hasil[] = $k;
                array_push($antrian, ...$k->dependensi());
            }
        }

        return array_values(array_filter(self::cases(), fn (self $k) => in_array($k, $hasil, true)));
    }
}
