<?php

namespace App\Console\Commands;

use App\Enums\KategoriReset;
use App\Services\ResetDataService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\confirm;

/**
 * CLI twin of Pengaturan → Reset Data (same ResetDataService, same backup),
 * for resetting over SSH. Categories must be named explicitly — there's no
 * "everything by default" — and production additionally requires --force.
 */
#[Signature('olimpiade:reset-data
    {--only= : Kategori dipisah koma: hasil-ujian, peserta, ujian, bank-soal, master-data}
    {--force : Jalankan tanpa konfirmasi (wajib di production)}')]
#[Description('Hapus data olimpiade per kategori (dengan backup Excel otomatis).')]
class ResetDataCommand extends Command
{
    public function handle(ResetDataService $service): int
    {
        $dipilih = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));
        $tidakDikenal = array_filter($dipilih, fn (string $k) => KategoriReset::tryFrom($k) === null);

        if ($dipilih === [] || $tidakDikenal !== []) {
            $this->error('Isi --only dengan kategori yang valid: '.implode(', ', array_column(KategoriReset::cases(), 'value')));

            return self::INVALID;
        }

        if ($this->laravel->isProduction() && ! $this->option('force')) {
            $this->error('Di production, perintah ini wajib dijalankan dengan --force.');

            return self::FAILURE;
        }

        $kategori = KategoriReset::lengkapi($dipilih);

        $this->info('Kategori: '.implode(', ', array_map(fn (KategoriReset $k) => $k->label(), $kategori)));
        $this->table(['Tabel', 'Jumlah baris'], collect($service->ringkasan($kategori))->map(fn (int $n, string $t) => [$t, $n])->values()->all());

        if (! $this->option('force') && ! confirm('Hapus data di atas? Tindakan ini tidak bisa dibatalkan.', default: false)) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        try {
            $hasil = $service->jalankan($kategori);
        } catch (ValidationException $e) {
            $this->error(collect($e->errors())->flatten()->first());

            return self::FAILURE;
        }

        $this->info('Selesai. Backup: storage/app/private/'.$hasil['backup']);

        return self::SUCCESS;
    }
}
