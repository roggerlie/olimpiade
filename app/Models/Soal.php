<?php

namespace App\Models;

use App\Models\Concerns\CatatAktivitas;
use Database\Factories\SoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'soal')]
#[Fillable(['bank_soal_id', 'jawaban', 'pertanyaan', 'pilih_a', 'pilih_b', 'pilih_c', 'pilih_d', 'pilih_e'])]
class Soal extends Model
{
    /** @use HasFactory<SoalFactory> */
    use CatatAktivitas, HasFactory;

    protected function atributDicatat(): array
    {
        return ['bank_soal_id'];
    }

    protected function jenisAktivitas(): string
    {
        return 'Soal';
    }

    public function labelAktivitas(): string
    {
        return "#{$this->id}";
    }

    /**
     * Soal content (pertanyaan/pilihan, HTML with inline base64 images) is
     * left out of the log on purpose — it would bloat activity_log, and the
     * answer key has no business in a log operators can read. An edit is
     * still recorded, just without old → new values.
     */
    protected function catatTanpaPerubahanAtribut(): bool
    {
        return true;
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class);
    }

    /**
     * The available answer choices, keyed by letter, skipping empty options.
     * Each value is HTML (TinyMCE's output, purified — see
     * App\Support\SoalContentPurifier), with any image the option carries
     * already embedded inline rather than tracked separately.
     *
     * @return array<string, string>
     */
    public function pilihan(): array
    {
        return collect(['A' => $this->pilih_a, 'B' => $this->pilih_b, 'C' => $this->pilih_c, 'D' => $this->pilih_d, 'E' => $this->pilih_e])
            ->filter(fn (?string $html) => filled($html))
            ->all();
    }
}
