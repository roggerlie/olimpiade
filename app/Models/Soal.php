<?php

namespace App\Models;

use Database\Factories\SoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'soal')]
#[Fillable(['bank_soal_id', 'pertanyaan', 'pilih_a', 'pilih_b', 'pilih_c', 'pilih_d', 'pilih_e', 'jawaban'])]
class Soal extends Model
{
    /** @use HasFactory<SoalFactory> */
    use HasFactory;

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class);
    }

    /**
     * The available answer choices, keyed by letter, skipping empty options.
     *
     * @return array<string, string>
     */
    public function pilihan(): array
    {
        return collect(['A' => $this->pilih_a, 'B' => $this->pilih_b, 'C' => $this->pilih_c, 'D' => $this->pilih_d, 'E' => $this->pilih_e])
            ->filter()
            ->all();
    }
}
