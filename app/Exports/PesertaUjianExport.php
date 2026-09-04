<?php

namespace App\Exports;

use App\Models\PesertaUjian;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Nilai peserta for one ujian, one row per registration — download link
 * lives on the ujian's peserta/monitoring page (already scoped to a single
 * ujian), so no extra jenjang/tanggal filters are needed here.
 */
class PesertaUjianExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly int $ujianId) {}

    public function query(): Builder
    {
        return PesertaUjian::query()
            ->with('peserta')
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
    public function map($pesertaUjian): array
    {
        return [
            $pesertaUjian->peserta->noreg,
            $pesertaUjian->peserta->nama,
            $pesertaUjian->peserta->asal_sekolah,
            $pesertaUjian->waktu_mulai?->format('Y-m-d H:i:s') ?? '-',
            $pesertaUjian->waktu_selesai?->format('Y-m-d H:i:s') ?? '-',
            $pesertaUjian->benar ?? '-',
            $pesertaUjian->salah ?? '-',
            $pesertaUjian->nilai ?? '-',
            match (true) {
                $pesertaUjian->sudahSubmit() => 'Selesai',
                (bool) $pesertaUjian->waktu_mulai => 'Sedang Mengerjakan',
                default => 'Terdaftar',
            },
        ];
    }
}
