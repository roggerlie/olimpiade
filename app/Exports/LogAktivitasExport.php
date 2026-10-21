<?php

namespace App\Exports;

use App\Support\LogAktivitas;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\Activitylog\Models\Activity;

/**
 * Log Aktivitas "Export Excel": exactly the rows the page lists for the
 * same filters (see LogAktivitas::filter() / cari()).
 */
class LogAktivitasExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array{log: string, dari: ?string, sampai: ?string, event: ?string, kataKunci: ?string}  $filter
     */
    public function __construct(private readonly array $filter) {}

    public function query(): Builder
    {
        return LogAktivitas::cari(...$this->filter);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Waktu', 'Pelaku', 'Aktivitas', 'Deskripsi', 'Keterangan', 'IP', 'Perangkat', 'Detail'];
    }

    /**
     * @param  Activity  $aktivitas
     * @return array<int, string|null>
     */
    public function map($aktivitas): array
    {
        return [
            $aktivitas->created_at->format('Y-m-d H:i:s'),
            LogAktivitas::pelakuDari($aktivitas),
            LogAktivitas::labelEvent($aktivitas),
            $aktivitas->description,
            LogAktivitas::keterangan($aktivitas),
            $aktivitas->properties['ip'] ?? '-',
            $aktivitas->properties['user_agent'] ?? '-',
            json_encode($aktivitas->properties->except(['ip', 'user_agent', 'pelaku']), JSON_UNESCAPED_UNICODE),
        ];
    }
}
