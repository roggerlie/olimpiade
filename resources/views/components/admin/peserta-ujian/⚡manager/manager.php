<?php

use App\Models\Peserta;
use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Ruangan;
use App\Models\Ujian;
use App\Services\PenempatanRuanganService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $ujianId;

    public string $search = '';

    /** '' = semua, 'tanpa' = terdaftar tapi belum punya ruangan, else a Ruangan id. */
    public string $filterRuangan = '';

    /**
     * Peserta ids ticked for "Pindahkan ke Ruangan" — only registered rows
     * get a checkbox. Cleared whenever the page, search or filter changes.
     *
     * @var array<int, int|string>
     */
    public array $selectedIds = [];

    /** Target of "Pindahkan ke Ruangan": a Ruangan id, or 'kosongkan' to unplace. */
    public string $targetRuangan = '';

    public bool $showBagiModal = false;

    /** @var array<int, int|string> */
    public array $bagiRuanganIds = [];

    public string $bagiUrutan = PenempatanRuanganService::URUTAN_CAMPUR_SEKOLAH;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(int $ujianId): void
    {
        $this->ujianId = $ujianId;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterRuangan'], true)) {
            $this->resetPage();
            $this->selectedIds = [];
        }
    }

    public function updatedPaginators(): void
    {
        $this->selectedIds = [];
    }

    #[Computed]
    public function ujian(): Ujian
    {
        return Ujian::with(['jenjang', 'pelajaran', 'bankSoal'])->findOrFail($this->ujianId);
    }

    #[Computed]
    public function peserta(): LengthAwarePaginator
    {
        return Peserta::query()
            ->where('jenjang_id', $this->ujian->jenjang_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('noreg', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterRuangan !== '', fn ($query) => $query->whereHas('pesertaUjian', function ($q) {
                $q->where('ujian_id', $this->ujianId);
                $this->filterRuangan === 'tanpa'
                    ? $q->whereNull('ruangan_id')
                    : $q->where('ruangan_id', $this->filterRuangan);
            }))
            ->with(['pesertaUjian' => fn ($q) => $q->where('ujian_id', $this->ujianId)->with('ruangan')])
            ->orderBy('nama')
            ->paginate(15);
    }

    #[Computed]
    public function ruanganPilihan(): EloquentCollection
    {
        return Ruangan::query()->orderBy('nama')->get();
    }

    /**
     * Occupancy of every room as seen from this ujian, including peserta of
     * other ujian held in the same room at an overlapping time.
     *
     * @return Collection<int, array{ruangan: Ruangan, terisi: int, terisiUjianLain: int, ujianLain: list<string>, total: int, sisa: int, melebihi: bool}>
     */
    #[Computed]
    public function pemakaianRuangan(): Collection
    {
        return app(PenempatanRuanganService::class)->pemakaian($this->ujian);
    }

    #[Computed]
    public function totalTanpaRuangan(): int
    {
        return PesertaUjian::query()->where('ujian_id', $this->ujianId)->whereNull('ruangan_id')->count();
    }

    /**
     * Registered peserta on the current page — the only rows with a checkbox.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function idsTerdaftarDiHalaman(): array
    {
        return $this->peserta->filter(fn (Peserta $peserta) => $peserta->pesertaUjian->isNotEmpty())->pluck('id')->all();
    }

    #[Computed]
    public function semuaDiHalamanTerpilih(): bool
    {
        $ids = array_map('strval', $this->idsTerdaftarDiHalaman);

        return $ids !== [] && array_diff($ids, array_map('strval', $this->selectedIds)) === [];
    }

    public function toggleSemuaDiHalaman(): void
    {
        $this->selectedIds = $this->semuaDiHalamanTerpilih ? [] : $this->idsTerdaftarDiHalaman;

        unset($this->semuaDiHalamanTerpilih);
    }

    /**
     * The per-row Ruangan dropdown. An empty value unplaces the peserta.
     */
    public function pindahkanRuangan(int $pesertaId, string $ruanganId): void
    {
        $pesertaUjian = PesertaUjian::query()
            ->where('peserta_id', $pesertaId)
            ->where('ujian_id', $this->ujianId)
            ->firstOrFail();

        $ruangan = $ruanganId === '' ? null : Ruangan::findOrFail($ruanganId);
        $pesertaUjian->update(['ruangan_id' => $ruangan?->id]);

        $this->statusMessage = $ruangan
            ? "{$pesertaUjian->peserta->nama} ditempatkan di {$ruangan->nama}."
            : "Ruangan {$pesertaUjian->peserta->nama} dikosongkan.";
        $this->errorMessage = null;
        $this->refreshLists();
    }

    /**
     * "Pindahkan ke Ruangan" for every ticked peserta. Over-capacity is only
     * warned about (see pemakaianRuangan()), never refused.
     */
    public function pindahkanTerpilih(): void
    {
        if ($this->selectedIds === [] || $this->targetRuangan === '') {
            $this->errorMessage = 'Centang peserta dan pilih ruangan tujuannya dulu.';
            $this->statusMessage = null;

            return;
        }

        $ruangan = $this->targetRuangan === 'kosongkan' ? null : Ruangan::findOrFail($this->targetRuangan);

        $jumlah = PesertaUjian::query()
            ->where('ujian_id', $this->ujianId)
            ->whereIn('peserta_id', array_map('intval', $this->selectedIds))
            ->update(['ruangan_id' => $ruangan?->id]);

        $this->statusMessage = $ruangan
            ? "{$jumlah} peserta dipindahkan ke {$ruangan->nama}."
            : "Ruangan {$jumlah} peserta dikosongkan.";
        $this->errorMessage = null;
        $this->selectedIds = [];
        $this->targetRuangan = '';
        $this->refreshLists();
    }

    public function bukaBagiOtomatis(): void
    {
        $this->reset(['bagiRuanganIds', 'bagiUrutan']);
        $this->resetErrorBag();
        $this->showBagiModal = true;
    }

    public function bagiOtomatis(PenempatanRuanganService $penempatan): void
    {
        $this->validate([
            'bagiRuanganIds' => ['required', 'array', 'min:1'],
            'bagiRuanganIds.*' => ['exists:ruangan,id'],
            'bagiUrutan' => ['required', Rule::in([PenempatanRuanganService::URUTAN_CAMPUR_SEKOLAH, PenempatanRuanganService::URUTAN_NOREG])],
        ], [
            'bagiRuanganIds.required' => 'Pilih minimal satu ruangan.',
            'bagiRuanganIds.min' => 'Pilih minimal satu ruangan.',
        ]);

        $hasil = $penempatan->bagiOtomatis($this->ujian, $this->bagiRuanganIds, $this->bagiUrutan);

        $this->statusMessage = "{$hasil['ditempatkan']} peserta dibagi ke ruangan.";
        $this->errorMessage = $hasil['tidakKebagian'] > 0
            ? "{$hasil['tidakKebagian']} peserta belum kebagian ruangan karena kapasitas ruangan yang dipilih sudah penuh. Pilih ruangan tambahan lalu bagi otomatis lagi, atau tempatkan manual."
            : null;
        $this->showBagiModal = false;
        $this->refreshLists();
    }

    #[Computed]
    public function totalPeserta(): int
    {
        return Peserta::query()->where('jenjang_id', $this->ujian->jenjang_id)->count();
    }

    #[Computed]
    public function totalTerdaftar(): int
    {
        return PesertaUjian::query()->where('ujian_id', $this->ujianId)->count();
    }

    /**
     * Only registers peserta who declared interest in this ujian's mapel
     * ("minat lomba", see App\Models\Peserta::pelajaranLomba()) — e.g.
     * flagged via the osains/omtk/obing columns on import, or the "Ikut
     * Lomba" checkboxes on Kelola Peserta — but were never registered
     * because this Ujian didn't exist yet at the time.
     */
    public function daftarkanYangBerminat(): void
    {
        $pesertaBerminat = Peserta::query()
            ->where('jenjang_id', $this->ujian->jenjang_id)
            ->whereHas('pelajaranLomba', fn ($q) => $q->where('pelajaran.id', $this->ujian->pelajaran_id))
            ->whereDoesntHave('pesertaUjian', fn ($q) => $q->where('ujian_id', $this->ujianId))
            ->get(['id']);

        DB::transaction(function () use ($pesertaBerminat): void {
            foreach ($pesertaBerminat as $peserta) {
                PesertaUjian::create([
                    'peserta_id' => $peserta->id,
                    'ujian_id' => $this->ujianId,
                ]);
            }
        });

        $this->statusMessage = "{$pesertaBerminat->count()} peserta yang berminat didaftarkan.";
        $this->errorMessage = null;
        $this->refreshLists();
    }

    public function toggleDaftar(int $pesertaId): void
    {
        $pesertaUjian = PesertaUjian::query()
            ->where('peserta_id', $pesertaId)
            ->where('ujian_id', $this->ujianId)
            ->first();

        if ($pesertaUjian) {
            if ($pesertaUjian->waktu_mulai) {
                $this->errorMessage = 'Peserta ini sudah mulai mengerjakan ujian — gunakan tombol Reset untuk membatalkan progresnya dulu.';

                return;
            }

            $pesertaUjian->delete();
            $this->statusMessage = 'Pendaftaran peserta dibatalkan.';
        } else {
            PesertaUjian::create(['peserta_id' => $pesertaId, 'ujian_id' => $this->ujianId]);
            $this->statusMessage = 'Peserta didaftarkan.';
        }

        $this->errorMessage = null;
        $this->refreshLists();
    }

    /**
     * Wipe one attempt's progress (answers, timing, score) so the student
     * can retake the exam from scratch. Registration itself is untouched.
     *
     * Named resetProgres, not reset — the latter collides with
     * Livewire\Component::reset(...$properties).
     */
    public function resetProgres(int $pesertaId): void
    {
        $pesertaUjian = PesertaUjian::query()
            ->where('peserta_id', $pesertaId)
            ->where('ujian_id', $this->ujianId)
            ->first();

        if (! $pesertaUjian) {
            return;
        }

        $this->resetSatuAttempt($pesertaUjian);

        $this->statusMessage = 'Progres peserta direset.';
        $this->errorMessage = null;
        $this->refreshLists();
    }

    /**
     * Reset every attempt for this ujian that has any progress — leaves
     * attempts that haven't started (nothing to reset) and registrations
     * with no attempt row untouched.
     */
    public function resetSemua(): void
    {
        $attempts = PesertaUjian::query()
            ->where('ujian_id', $this->ujianId)
            ->whereNotNull('waktu_mulai')
            ->get();

        foreach ($attempts as $attempt) {
            $this->resetSatuAttempt($attempt);
        }

        $this->statusMessage = "{$attempts->count()} progres peserta direset.";
        $this->errorMessage = null;
        $this->refreshLists();
    }

    private function resetSatuAttempt(PesertaUjian $pesertaUjian): void
    {
        DB::transaction(function () use ($pesertaUjian): void {
            PesertaSoal::query()->where('peserta_ujian_id', $pesertaUjian->id)->delete();

            $pesertaUjian->update([
                'waktu_mulai' => null,
                'waktu_selesai' => null,
                'benar' => null,
                'salah' => null,
                'nilai' => null,
            ]);
        });
    }

    private function refreshLists(): void
    {
        unset($this->peserta, $this->totalTerdaftar, $this->pemakaianRuangan, $this->totalTanpaRuangan, $this->idsTerdaftarDiHalaman, $this->semuaDiHalamanTerpilih);
    }
};
