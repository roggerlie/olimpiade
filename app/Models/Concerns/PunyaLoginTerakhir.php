<?php

namespace App\Models\Concerns;

use App\Support\LogAktivitas;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * For the two login-able models (User, Peserta): withLoginTerakhir() adds a
 * `login_terakhir` datetime column — the newest successful login recorded
 * in the `auth` activity log — for the "Login Terakhir" column on Kelola
 * Pengguna / Kelola Peserta. Null when never logged in (or the entry is
 * older than the log's retention, see config/activitylog.php).
 */
trait PunyaLoginTerakhir
{
    #[Scope]
    protected function withLoginTerakhir(Builder $query): void
    {
        $query
            ->addSelect(['login_terakhir' => Activity::query()
                ->select('created_at')
                ->where('log_name', LogAktivitas::AUTH)
                ->where('event', 'login')
                ->where('causer_type', $this->getMorphClass())
                ->whereColumn('causer_id', $this->qualifyColumn($this->getKeyName()))
                ->latest()
                ->limit(1),
            ])
            ->withCasts(['login_terakhir' => 'datetime']);
    }
}
