<?php

namespace App\Models;

use Database\Factories\SiswaUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Table(name: 'siswa_ujian')]
#[Fillable(['siswa_id', 'ujian_id', 'waktu_mulai', 'waktu_selesai', 'benar', 'salah', 'nilai'])]
class SiswaUjian extends Model
{
    /** @use HasFactory<SiswaUjianFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'nilai' => 'decimal:2',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class);
    }

    public function siswaSoal(): HasMany
    {
        return $this->hasMany(SiswaSoal::class);
    }

    /**
     * A submission is final once `waktu_selesai` is recorded — either the
     * student submitted manually or the auto-submit scheduler closed it out.
     */
    public function sudahSubmit(): bool
    {
        return $this->waktu_selesai !== null;
    }

    /**
     * The hard deadline for this attempt: whichever comes first, the
     * student's personal duration allowance or the exam's session window.
     */
    public function batasWaktu(): Carbon
    {
        $batasDurasi = $this->waktu_mulai?->copy()->addSeconds($this->ujian->durasi_detik);

        return $batasDurasi?->min($this->ujian->sesi_selesai) ?? $this->ujian->sesi_selesai;
    }

    public function waktuHabis(): bool
    {
        return $this->waktu_mulai !== null && now()->greaterThanOrEqualTo($this->batasWaktu());
    }

    /**
     * Wall-clock time spent on the attempt, in seconds — null until both
     * ends are recorded. Used as the leaderboard tie-breaker: same nilai,
     * faster finish ranks higher.
     */
    public function durasiPengerjaan(): ?int
    {
        if ($this->waktu_mulai === null || $this->waktu_selesai === null) {
            return null;
        }

        return $this->waktu_mulai->diffInSeconds($this->waktu_selesai);
    }
}
