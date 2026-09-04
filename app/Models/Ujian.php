<?php

namespace App\Models;

use Database\Factories\UjianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'ujian')]
#[Fillable([
    'bank_soal_id', 'jenjang_id', 'pelajaran_id', 'nama',
    'jumlah_soal', 'durasi_detik', 'sesi_mulai', 'sesi_selesai', 'deskripsi',
])]
class Ujian extends Model
{
    /** @use HasFactory<UjianFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sesi_mulai' => 'datetime',
            'sesi_selesai' => 'datetime',
        ];
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class);
    }

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function pelajaran(): BelongsTo
    {
        return $this->belongsTo(Pelajaran::class);
    }

    public function pesertaUjian(): HasMany
    {
        return $this->hasMany(PesertaUjian::class);
    }

    /**
     * Whether the exam session window is currently open for students to start or continue.
     */
    public function sesiSedangBerlangsung(): bool
    {
        return now()->between($this->sesi_mulai, $this->sesi_selesai);
    }
}
