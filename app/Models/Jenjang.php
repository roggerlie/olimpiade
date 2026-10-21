<?php

namespace App\Models;

use App\Models\Concerns\CatatAktivitas;
use Database\Factories\JenjangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'jenjang')]
#[Fillable(['kode', 'nama'])]
class Jenjang extends Model
{
    /** @use HasFactory<JenjangFactory> */
    use CatatAktivitas, HasFactory;

    protected function atributDicatat(): array
    {
        return ['kode', 'nama'];
    }

    protected function jenisAktivitas(): string
    {
        return 'Jenjang';
    }

    public function labelAktivitas(): string
    {
        return $this->nama;
    }

    public function bankSoal(): HasMany
    {
        return $this->hasMany(BankSoal::class);
    }

    public function ujian(): HasMany
    {
        return $this->hasMany(Ujian::class);
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(Peserta::class);
    }
}
