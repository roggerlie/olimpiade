<?php

namespace App\Models;

use Database\Factories\PelajaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'pelajaran')]
#[Fillable(['nama'])]
class Pelajaran extends Model
{
    /** @use HasFactory<PelajaranFactory> */
    use HasFactory;

    public function bankSoal(): HasMany
    {
        return $this->hasMany(BankSoal::class);
    }

    public function ujian(): HasMany
    {
        return $this->hasMany(Ujian::class);
    }

    /**
     * Peserta who declared interest in competing in this mapel ("minat
     * lomba") — see App\Models\Peserta::pelajaranLomba() for the inverse
     * and what this is deliberately not (a PesertaUjian registration).
     */
    public function pesertaLomba(): BelongsToMany
    {
        return $this->belongsToMany(Peserta::class, 'peserta_pelajaran');
    }
}
