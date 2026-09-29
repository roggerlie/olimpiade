<?php

namespace App\Exports\Sheets;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * One raw table dumped as a sheet of BackupSebelumResetExport.
 */
class TabelBackupSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    /**
     * Never written to a backup: a downloaded .xlsx is easy to pass around,
     * and `password_plain` in it would hand out every peserta's login.
     */
    private const KOLOM_RAHASIA = ['password', 'password_plain', 'remember_token'];

    /** @var list<string> */
    private readonly array $kolom;

    public function __construct(private readonly string $tabel)
    {
        $this->kolom = array_values(array_diff(Schema::getColumnListing($tabel), self::KOLOM_RAHASIA));
    }

    public function query(): Builder
    {
        return DB::table($this->tabel)->select($this->kolom)->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->kolom;
    }

    /**
     * @param  object  $row
     * @return list<mixed>
     */
    public function map($row): array
    {
        return array_map(fn (string $kolom) => $row->{$kolom}, $this->kolom);
    }

    public function title(): string
    {
        return $this->tabel;
    }
}
