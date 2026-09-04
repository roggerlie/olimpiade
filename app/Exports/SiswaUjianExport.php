<?php

namespace App\Exports;

use App\Models\SiswaUjian;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Nilai peserta for one ujian, one row per registration — download link
 * lives on the ujian's peserta/monitoring page (already scoped to a single
 * ujian), so no extra jenjang/tanggal filters are needed here.
 */
class SiswaUjianExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly int $ujianId) {}

    public function query(): Builder
    {
        return SiswaUjian::query()
            ->with('siswa')
            ->where('ujian_id', $this->ujianId)
            ->orderByDesc('nilai');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['No. Registrasi', 'Nama', 'Asal Sekolah', 'Waktu Mulai', 'Waktu Selesai', 'Benar', 'Salah', 'Nilai', 'Status'];
    }

    /**
     * @return array<int, string|int|null>
     */
    public function map($siswaUjian): array
    {
        return [
            $siswaUjian->siswa->noreg,
            $siswaUjian->siswa->nama,
            $siswaUjian->siswa->asal_sekolah,
            $siswaUjian->waktu_mulai?->format('Y-m-d H:i:s') ?? '-',
            $siswaUjian->waktu_selesai?->format('Y-m-d H:i:s') ?? '-',
            $siswaUjian->benar ?? '-',
            $siswaUjian->salah ?? '-',
            $siswaUjian->nilai ?? '-',
            match (true) {
                $siswaUjian->sudahSubmit() => 'Selesai',
                (bool) $siswaUjian->waktu_mulai => 'Sedang Mengerjakan',
                default => 'Terdaftar',
            },
        ];
    }
}
