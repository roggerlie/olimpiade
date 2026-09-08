<?php

use App\Models\Soal;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $bankSoalId;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(int $bankSoalId): void
    {
        $this->bankSoalId = $bankSoalId;
        // Tambah/Ubah Soal live on their own page now (not this component's
        // modal) — see admin.soal.form — so a just-saved status message
        // arrives via a session flash instead of being set directly here.
        $this->statusMessage = session('status');
    }

    public function updated(string $property): void
    {
        if ($property === 'search') {
            $this->resetPage();
        }
    }

    #[On('soal-imported')]
    public function refreshList(): void
    {
        unset($this->soal, $this->totalSoal);
    }

    #[Computed]
    public function soal(): LengthAwarePaginator
    {
        return Soal::query()
            ->where('bank_soal_id', $this->bankSoalId)
            ->when($this->search, fn ($query) => $query->where('pertanyaan', 'like', "%{$this->search}%"))
            ->orderBy('id')
            ->paginate(10);
    }

    /**
     * Unfiltered count, so the "N Soal" badge next to + Tambah Soal always
     * reads as the bank's real total — independent of what `search` narrows
     * the list below down to.
     */
    #[Computed]
    public function totalSoal(): int
    {
        return Soal::query()->where('bank_soal_id', $this->bankSoalId)->count();
    }

    public function delete(int $id): void
    {
        $this->authorize('bank-soal.manage');

        try {
            Soal::findOrFail($id)->delete();
            $this->statusMessage = 'Soal dihapus.';
            $this->errorMessage = null;
            unset($this->totalSoal);
        } catch (QueryException) {
            $this->errorMessage = 'Soal tidak bisa dihapus karena sudah dijawab oleh peserta pada suatu ujian.';
        }

        unset($this->soal);
    }
};
