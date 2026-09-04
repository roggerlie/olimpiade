<?php

use App\Models\Peserta;
use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $ujianId;

    public string $search = '';

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(int $ujianId): void
    {
        $this->ujianId = $ujianId;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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
            ->with(['pesertaUjian' => fn ($q) => $q->where('ujian_id', $this->ujianId)])
            ->orderBy('nama')
            ->paginate(15);
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

    public function daftarkanSemua(): void
    {
        $pesertaBelumTerdaftar = Peserta::query()
            ->where('jenjang_id', $this->ujian->jenjang_id)
            ->whereDoesntHave('pesertaUjian', fn ($q) => $q->where('ujian_id', $this->ujianId))
            ->get(['id']);

        DB::transaction(function () use ($pesertaBelumTerdaftar): void {
            foreach ($pesertaBelumTerdaftar as $peserta) {
                PesertaUjian::create([
                    'peserta_id' => $peserta->id,
                    'ujian_id' => $this->ujianId,
                ]);
            }
        });

        $this->statusMessage = "{$pesertaBelumTerdaftar->count()} peserta baru didaftarkan.";
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
        unset($this->peserta, $this->totalTerdaftar);
    }
};
