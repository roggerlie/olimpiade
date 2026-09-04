<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * The downloadable column template for App\Imports\PesertaImport — keep the
 * headings here in sync with the columns that import reads.
 */
class PesertaImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['noreg', 'nama', 'jenjang', 'asal_sekolah', 'password'];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['1234567', 'Contoh Nama Peserta', '01', 'SD Contoh', ''],
        ];
    }
}
