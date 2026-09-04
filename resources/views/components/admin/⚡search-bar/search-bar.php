<?php

use App\Models\Peserta;
use App\Models\Ujian;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Global admin search — TailAdmin's header search box, wired to actually
 * search this app's data instead of being decorative. Peserta match on
 * nama or noreg; Ujian match on nama. Peserta results deep-link into the
 * Peserta manager's own #[Url] search filter, so clicking a peserta lands
 * directly on it pre-filtered.
 */
new class extends Component
{
    public string $query = '';

    private const MIN_QUERY_LENGTH = 2;

    #[Computed]
    public function pesertaResults(): Collection
    {
        if (mb_strlen($this->query) < self::MIN_QUERY_LENGTH) {
            return collect();
        }

        return Peserta::query()
            ->with('jenjang')
            ->where(fn ($q) => $q->where('nama', 'like', "%{$this->query}%")
                ->orWhere('noreg', 'like', "%{$this->query}%"))
            ->orderBy('nama')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function ujianResults(): Collection
    {
        if (mb_strlen($this->query) < self::MIN_QUERY_LENGTH) {
            return collect();
        }

        return Ujian::query()
            ->where('nama', 'like', "%{$this->query}%")
            ->orderBy('nama')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function hasResults(): bool
    {
        return $this->pesertaResults->isNotEmpty() || $this->ujianResults->isNotEmpty();
    }
};
