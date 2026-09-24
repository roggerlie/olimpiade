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
        return ['noreg', 'nama', 'jenjang', 'asal_sekolah', 'password', 'osains', 'omtk', 'obing'];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['0123456789', 'Contoh Nama Peserta', 'SLTA', 'SD Contoh', 'acak', '1', '1', '0'],
        ];
    }
}
