<?php

namespace App\Exports;

use App\Models\Peserta;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Kelola Peserta's "Export Excel": the peserta ticked in the list when
 * $ids is given ("Export Terpilih"), otherwise every peserta matching the
 * list's current search/jenjang/pelajaran filter (Peserta::filterAdmin()), with their login
 * credentials (noreg + password_plain) so the admin can print and hand them
 * out. Laid out print-ready: bordered, landscape, fit to one page wide, and
 * the heading row repeated on every printed page.
 *
 * Extends StringValueBinder so every cell is written as text — a NISN with a
 * leading zero (or an all-digit password) must never be turned into a number.
 */
class PesertaExport extends StringValueBinder implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles
{
    private int $nomor = 0;

    /**
     * @param  array<int, int>|null  $ids
     */
    public function __construct(
        private readonly ?string $search = null,
        private readonly int|string|null $jenjangId = null,
        private readonly int|string|null $pelajaranId = null,
        private readonly ?array $ids = null,
    ) {}

    public function query(): Builder
    {
        return Peserta::query()
            ->with(['jenjang', 'pelajaranLomba'])
            ->when(
                $this->ids !== null,
                fn (Builder $query) => $query->whereIn('id', $this->ids),
                fn (Builder $query) => $query->filterAdmin($this->search, $this->jenjangId, $this->pelajaranId),
            )
            ->orderBy('noreg');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['No', 'No. Registrasi (Username)', 'Nama', 'Jenjang', 'Asal Sekolah', 'Password', 'Lomba'];
    }

    /**
     * @return array<int, string|int>
     */
    public function map($peserta): array
    {
        $this->nomor++;

        return [
            $this->nomor,
            $peserta->noreg,
            $peserta->nama,
            $peserta->jenjang->nama,
            $peserta->asal_sekolah,
            $peserta->password_plain ?? '-',
            $peserta->pelajaranLomba->pluck('nama')->join(', ') ?: '-',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $range = 'A1:'.$sheet->getHighestColumn().$sheet->getHighestRow();

                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('F2:F'.$sheet->getHighestRow())->getFont()->setName('Courier New')->setBold(true);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0)
                    ->setRowsToRepeatAtTopByStartAndEnd(1, 1);
            },
        ];
    }
}
