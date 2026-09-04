<?php

namespace App\Models;

use Database\Factories\PesertaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'peserta')]
#[Fillable(['user_id', 'jenjang_id', 'noreg', 'nama', 'asal_sekolah'])]
class Peserta extends Model
{
    /** @use HasFactory<PesertaFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function pesertaUjian(): HasMany
    {
        return $this->hasMany(PesertaUjian::class);
    }
}
