<?php

namespace App\Models;

use Database\Factories\PesertaSoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'peserta_soal')]
#[Fillable(['peserta_ujian_id', 'soal_id', 'urutan', 'jawaban'])]
class PesertaSoal extends Model
{
    /** @use HasFactory<PesertaSoalFactory> */
    use HasFactory;

    public function pesertaUjian(): BelongsTo
    {
        return $this->belongsTo(PesertaUjian::class);
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
