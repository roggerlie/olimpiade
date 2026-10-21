<?php

use App\Support\LogAktivitas;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Sistem → Log Aktivitas: browse the `auth` / `admin` / `ujian` activity
 * logs (see App\Support\LogAktivitas). Read-only, except "Hapus Log Lama":
 * administrator only, drops entries older than
 * activitylog.delete_records_older_than_days — there's no scheduler for it.
 */
new class extends Component
{
    use WithPagination;

    #[Url]
    public string $log = LogAktivitas::AUTH;

    #[Url]
    public string $dari = '';

    #[Url]
    public string $sampai = '';

    #[Url]
    public string $event = '';

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $detailId = null;

    public bool $showDetail = false;

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->authorize('log-aktivitas.view');

        if (! array_key_exists($this->log, LogAktivitas::LOG)) {
            $this->log = LogAktivitas::AUTH;
        }

        $this->dari = $this->dari ?: now()->subDays(6)->toDateString();
        $this->sampai = $this->sampai ?: now()->toDateString();
    }

    public function updated(string $property): void
    {
        if ($property === 'log') {
            $this->reset('event');
        }

        $this->resetPage();
    }

    public function pilihLog(string $log): void
    {
        if (! array_key_exists($log, LogAktivitas::LOG)) {
            return;
        }

        $this->log = $log;
        $this->reset('event');
        $this->resetPage();
    }

    #[Computed]
    public function aktivitas(): LengthAwarePaginator
    {
        return LogAktivitas::cari(...LogAktivitas::filter($this->parameterExport))->paginate(25);
    }

    #[Computed]
    public function detail(): ?Activity
    {
        return $this->detailId ? Activity::with('causer', 'subject')->find($this->detailId) : null;
    }

    /**
     * Query string for the Export Excel link — the same filters as the list.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function parameterExport(): array
    {
        return array_filter([
            'log' => $this->log,
            'dari' => $this->dari,
            'sampai' => $this->sampai,
            'event' => $this->event,
            'q' => trim($this->search),
        ]);
    }

    #[Computed]
    public function jumlahLogLama(): int
    {
        return LogAktivitas::logLama()->count();
    }

    public function hapusLogLama(): void
    {
        abort_unless(auth()->user()?->hasRole('administrator'), 403);

        $jumlah = LogAktivitas::logLama()->delete();

        // Written after the delete, so this entry is never among what it removed.
        if ($jumlah > 0) {
            LogAktivitas::admin('hapus_log', "{$jumlah} log lama dihapus", properti: [
                'jumlah' => $jumlah,
                'lebih_dari_hari' => config('activitylog.delete_records_older_than_days'),
            ]);
        }

        $this->statusMessage = $jumlah > 0 ? "{$jumlah} log lama dihapus." : 'Tidak ada log lama untuk dihapus.';
        unset($this->jumlahLogLama, $this->aktivitas);
    }

    public function lihat(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
        unset($this->detail);
    }

    public function tutupDetail(): void
    {
        $this->showDetail = false;
        $this->detailId = null;
    }
};
