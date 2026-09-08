<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * The downloadable column template for App\Imports\SoalImport — keep the
 * headings here in sync with the columns that import reads. Text only; soal
 * with gambar need the Word template instead.
 */
class SoalImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'pilihan_e', 'jawaban'];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Berapa hasil dari 12 + 8?', '18', '20', '22', '24', '', 'B'],
        ];
    }
}
