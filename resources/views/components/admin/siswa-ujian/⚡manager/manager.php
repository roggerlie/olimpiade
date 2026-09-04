<?php

use App\Models\Siswa;
use App\Models\SiswaSoal;
use App\Models\SiswaUjian;
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
    public function siswa(): LengthAwarePaginator
    {
        return Siswa::query()
            ->where('jenjang_id', $this->ujian->jenjang_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('noreg', 'like', "%{$this->search}%");
                });
            })
            ->with(['siswaUjian' => fn ($q) => $q->where('ujian_id', $this->ujianId)])
            ->orderBy('nama')
            ->paginate(15);
    }

    #[Computed]
    public function totalPeserta(): int
    {
        return Siswa::query()->where('jenjang_id', $this->ujian->jenjang_id)->count();
    }

    #[Computed]
    public function totalTerdaftar(): int
    {
        return SiswaUjian::query()->where('ujian_id', $this->ujianId)->count();
    }

    public function daftarkanSemua(): void
    {
        $siswaBelumTerdaftar = Siswa::query()
            ->where('jenjang_id', $this->ujian->jenjang_id)
            ->whereDoesntHave('siswaUjian', fn ($q) => $q->where('ujian_id', $this->ujianId))
            ->get(['id']);

        DB::transaction(function () use ($siswaBelumTerdaftar): void {
            foreach ($siswaBelumTerdaftar as $siswa) {
                SiswaUjian::create([
                    'siswa_id' => $siswa->id,
                    'ujian_id' => $this->ujianId,
                ]);
            }
        });

        $this->statusMessage = "{$siswaBelumTerdaftar->count()} peserta baru didaftarkan.";
        $this->errorMessage = null;
        $this->refreshLists();
    }

    public function toggleDaftar(int $siswaId): void
    {
        $siswaUjian = SiswaUjian::query()
            ->where('siswa_id', $siswaId)
            ->where('ujian_id', $this->ujianId)
            ->first();

        if ($siswaUjian) {
            if ($siswaUjian->waktu_mulai) {
                $this->errorMessage = 'Peserta ini sudah mulai mengerjakan ujian — gunakan tombol Reset untuk membatalkan progresnya dulu.';

                return;
            }

            $siswaUjian->delete();
            $this->statusMessage = 'Pendaftaran peserta dibatalkan.';
        } else {
            SiswaUjian::create(['siswa_id' => $siswaId, 'ujian_id' => $this->ujianId]);
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
    public function resetProgres(int $siswaId): void
    {
        $siswaUjian = SiswaUjian::query()
            ->where('siswa_id', $siswaId)
            ->where('ujian_id', $this->ujianId)
            ->first();

        if (! $siswaUjian) {
            return;
        }

        $this->resetSatuAttempt($siswaUjian);

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
        $attempts = SiswaUjian::query()
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

    private function resetSatuAttempt(SiswaUjian $siswaUjian): void
    {
        DB::transaction(function () use ($siswaUjian): void {
            SiswaSoal::query()->where('siswa_ujian_id', $siswaUjian->id)->delete();

            $siswaUjian->update([
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
        unset($this->siswa, $this->totalTerdaftar);
    }
};
