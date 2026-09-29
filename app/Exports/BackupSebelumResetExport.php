<?php

namespace App\Exports;

use App\Enums\KategoriReset;
use App\Exports\Sheets\TabelBackupSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Snapshot taken right before App\Services\ResetDataService wipes data: one
 * sheet per table about to be deleted, raw columns as stored (HTML soal
 * content included) so it doubles as a record of exactly what was there.
 */
class BackupSebelumResetExport implements Export, WithMultipleSheets
{
    /**
     * @param  list<KategoriReset>  $kategori  Already resolved via KategoriReset::lengkapi().
     */
    public function __construct(private readonly array $kategori) {}

    /**
     * Parent-first (reverse of delete order) so the workbook reads top-down:
     * Jenjang, Pelajaran, Bank Soal, Soal, ... Hasil Ujian.
     *
     * @return list<TabelBackupSheet>
     */
    public function sheets(): array
    {
        return collect($this->kategori)
            ->flatMap(fn (KategoriReset $k) => $k->tabel())
            ->reverse()
            ->map(fn (string $tabel) => new TabelBackupSheet($tabel))
            ->values()
            ->all();
    }
}
