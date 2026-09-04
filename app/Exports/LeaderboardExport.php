<?php

namespace App\Exports;

use App\Models\Ujian;
use App\Services\LeaderboardService;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Ranked, submitted-only results for one ujian — same order as
 * App\Services\LeaderboardService, with an explicit Peringkat column so the
 * sheet is ready to cross-reference for certificates without re-sorting.
 */
class LeaderboardExport implements FromCollection, WithHeadings, WithMapping
{
    private int $peringkat = 0;

    public function __construct(private readonly Ujian $ujian) {}

    public function collection(): Enumerable
    {
        return app(LeaderboardService::class)->ranking($this->ujian);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Peringkat', 'No. Registrasi', 'Nama', 'Asal Sekolah', 'Benar', 'Salah', 'Nilai', 'Waktu Pengerjaan'];
    }

    /**
     * @return array<int, string|int|null>
     */
    public function map($siswaUjian): array
    {
        $this->peringkat++;

        $durasi = $siswaUjian->durasiPengerjaan();

        return [
            $this->peringkat,
            $siswaUjian->siswa->noreg,
            $siswaUjian->siswa->nama,
            $siswaUjian->siswa->asal_sekolah,
            $siswaUjian->benar,
            $siswaUjian->salah,
            $siswaUjian->nilai,
            $durasi !== null ? gmdate('H:i:s', $durasi) : '-',
        ];
    }
}
