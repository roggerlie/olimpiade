<?php

namespace App\Models;

use App\Models\Concerns\CatatAktivitas;
use Database\Factories\RuanganFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A physical exam room (master data). Peserta are placed into one per
 * registration — PesertaUjian::ruangan() — so the same peserta can sit
 * different lomba in different rooms. See App\Services\PenempatanRuanganService
 * for occupancy (kapasitas is a warning, never enforced) and "Bagi Otomatis".
 */
#[Table(name: 'ruangan')]
#[Fillable(['nama', 'kapasitas', 'keterangan'])]
class Ruangan extends Model
{
    /** @use HasFactory<RuanganFactory> */
    use CatatAktivitas, HasFactory;

    protected function atributDicatat(): array
    {
        return ['nama', 'kapasitas', 'keterangan'];
    }

    protected function jenisAktivitas(): string
    {
        return 'Ruangan';
    }

    public function labelAktivitas(): string
    {
        return $this->nama;
    }

    public function pesertaUjian(): HasMany
    {
        return $this->hasMany(PesertaUjian::class);
    }
}
