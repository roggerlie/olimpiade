<?php

namespace App\Models;

use Database\Factories\SiswaSoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'siswa_soal')]
#[Fillable(['siswa_ujian_id', 'soal_id', 'urutan', 'jawaban'])]
class SiswaSoal extends Model
{
    /** @use HasFactory<SiswaSoalFactory> */
    use HasFactory;

    public function siswaUjian(): BelongsTo
    {
        return $this->belongsTo(SiswaUjian::class);
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
