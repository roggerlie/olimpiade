<?php

namespace App\Models;

use Database\Factories\BankSoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'bank_soal')]
#[Fillable(['jenjang_id', 'pelajaran_id', 'nama', 'deskripsi'])]
class BankSoal extends Model
{
    /** @use HasFactory<BankSoalFactory> */
    use HasFactory;

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function pelajaran(): BelongsTo
    {
        return $this->belongsTo(Pelajaran::class);
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    public function ujian(): HasMany
    {
        return $this->hasMany(Ujian::class);
    }
}
