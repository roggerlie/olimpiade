<?php

namespace App\Models;

use Database\Factories\PesertaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A peserta's own login account — authenticated via the `peserta` guard
 * (see config/auth.php), entirely separate from App\Models\User (admin
 * accounts only). Logs in with `noreg` (their NISN), not a separate
 * username: the two were always kept in sync under the old paired-User
 * design, so there's nothing to keep distinct by having its own column.
 *
 * `password_plain` is a deliberate, display-only mirror of the last
 * password set — peserta have no email on file for a self-service reset,
 * so the admin hands out credentials directly (Kelola Peserta, kartu
 * peserta print). It's never read for authentication, only kept in sync
 * by whatever sets `password` (see App\Imports\PesertaImport and the
 * peserta manager Livewire component).
 */
#[Table(name: 'peserta')]
#[Fillable(['jenjang_id', 'noreg', 'nama', 'asal_sekolah', 'password', 'password_plain'])]
#[Hidden(['password', 'password_plain', 'remember_token'])]
class Peserta extends Authenticatable
{
    /** @use HasFactory<PesertaFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function pesertaUjian(): HasMany
    {
        return $this->hasMany(PesertaUjian::class);
    }

    /**
     * Mapel this peserta declared interest in competing in ("minat lomba")
     * — deliberately separate from pesertaUjian(): a peserta can be
     * interested in a mapel long before any Ujian exists for their
     * jenjang+mapel combo. See App\Imports\PesertaImport and the
     * "Daftarkan Peserta yang Berminat" action on the Peserta Ujian
     * admin page.
     */
    public function pelajaranLomba(): BelongsToMany
    {
        return $this->belongsToMany(Pelajaran::class, 'peserta_pelajaran');
    }
}
