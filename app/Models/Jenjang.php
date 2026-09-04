<?php

namespace App\Models;

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
    use HasFactory;

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
