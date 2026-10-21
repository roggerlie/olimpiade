<?php

namespace App\Models\Concerns;

use App\Support\LogAktivitas;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Logs this model's create/update/delete to the `admin` activity log
 * (see App\Support\LogAktivitas), recording only the attributes named by
 * atributDicatat() — old → new, dirty ones only. An update that touches
 * nothing in that list (e.g. a login cycling remember_token) isn't logged.
 *
 * `password` may be listed so a password change shows up at all, but its
 * value never reaches the log — it's masked in tapActivity() below.
 *
 * Bulk writes (query-builder update()/delete(), imports run inside
 * activity()->withoutLogs()) don't pass through here; their callers log a
 * single summary entry themselves.
 */
trait CatatAktivitas
{
    use LogsActivity;

    /**
     * Attribute names recorded in the log, old → new.
     *
     * @return list<string>
     */
    abstract protected function atributDicatat(): array;

    /** Human label for this model in the log, e.g. "Peserta". */
    abstract protected function jenisAktivitas(): string;

    /** Identifies this row in the log, kept even after it's deleted. */
    abstract public function labelAktivitas(): string;

    /**
     * Whether an update is still logged when none of atributDicatat()
     * changed (e.g. Soal: its HTML content is deliberately left out of the
     * log, but an edit to it should still show up as "diubah").
     */
    protected function catatTanpaPerubahanAtribut(): bool
    {
        return false;
    }

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->useLogName(LogAktivitas::ADMIN)
            ->logOnly($this->atributDicatat())
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => $this->jenisAktivitas().' '.match ($event) {
                'created' => 'dibuat',
                'updated' => 'diubah',
                'deleted' => 'dihapus',
                default => $event,
            });

        return $this->catatTanpaPerubahanAtribut() ? $options->submitEmptyLogs() : $options->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $properti = $activity->properties->toArray();

        foreach (['attributes', 'old'] as $bagian) {
            if (array_key_exists('password', $properti[$bagian] ?? [])) {
                $properti[$bagian]['password'] = '••••••';
            }
        }

        $activity->properties = collect($properti + ['label' => $this->labelAktivitas()] + LogAktivitas::konteksRequest() + [
            'pelaku' => $activity->causer ? LogAktivitas::namaPelaku($activity->causer) : null,
        ]);
    }
}
