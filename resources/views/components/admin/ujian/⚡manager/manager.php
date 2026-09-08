<?php

use App\Models\BankSoal;
use App\Models\Ujian;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public ?int $bankSoalId = null;

    public string $nama = '';

    public ?int $jumlahSoal = null;

    public string $sesiMulai = '';

    public string $sesiSelesai = '';

    public ?string $deskripsi = null;

    public bool $showModal = false;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    #[Computed]
    public function ujian(): LengthAwarePaginator
    {
        return Ujian::query()
            ->with(['bankSoal', 'jenjang', 'pelajaran'])
            ->orderByDesc('sesi_mulai')
            ->paginate(10);
    }

    #[Computed]
    public function bankSoalPilihan(): Collection
    {
        return BankSoal::query()
            ->with(['jenjang', 'pelajaran'])
            ->withCount('soal')
            ->orderBy('nama')
            ->get();
    }

    public function create(): void
    {
        $this->authorize('ujian.manage');

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('ujian.manage');

        $ujian = Ujian::findOrFail($id);

        $this->editingId = $ujian->id;
        $this->bankSoalId = $ujian->bank_soal_id;
        $this->nama = $ujian->nama;
        $this->jumlahSoal = $ujian->jumlah_soal;
        $this->sesiMulai = $ujian->sesi_mulai->format('Y-m-d\TH:i');
        $this->sesiSelesai = $ujian->sesi_selesai->format('Y-m-d\TH:i');
        $this->deskripsi = $ujian->deskripsi;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('ujian.manage');

        $validator = Validator::make(
            [
                'bankSoalId' => $this->bankSoalId,
                'nama' => $this->nama,
                'jumlahSoal' => $this->jumlahSoal,
                'sesiMulai' => $this->sesiMulai,
                'sesiSelesai' => $this->sesiSelesai,
                'deskripsi' => $this->deskripsi,
            ],
            [
                'bankSoalId' => ['required', 'exists:bank_soal,id'],
                'nama' => ['required', 'string', 'max:255'],
                'jumlahSoal' => ['required', 'integer', 'min:1'],
                'sesiMulai' => ['required', 'date'],
                'sesiSelesai' => ['required', 'date', 'after:sesiMulai'],
                'deskripsi' => ['nullable', 'string'],
            ]
        )->after(function ($validator) {
            $bankSoal = BankSoal::withCount('soal')->find($this->bankSoalId);

            if ($bankSoal && $this->jumlahSoal && $this->jumlahSoal > $bankSoal->soal_count) {
                $validator->errors()->add(
                    'jumlahSoal',
                    "Jumlah soal ({$this->jumlahSoal}) melebihi soal yang tersedia di bank ini ({$bankSoal->soal_count})."
                );
            }
        });

        $data = $validator->validate();

        $bankSoal = BankSoal::findOrFail($data['bankSoalId']);
        $sesiMulai = Carbon::parse($data['sesiMulai']);
        $sesiSelesai = Carbon::parse($data['sesiSelesai']);

        $payload = [
            'bank_soal_id' => $bankSoal->id,
            'jenjang_id' => $bankSoal->jenjang_id,
            'pelajaran_id' => $bankSoal->pelajaran_id,
            'nama' => $data['nama'],
            'jumlah_soal' => $data['jumlahSoal'],
            'durasi_detik' => $sesiMulai->diffInSeconds($sesiSelesai),
            'sesi_mulai' => $sesiMulai,
            'sesi_selesai' => $sesiSelesai,
            'deskripsi' => $data['deskripsi'],
        ];

        if ($this->editingId) {
            Ujian::findOrFail($this->editingId)->update($payload);
            $this->statusMessage = 'Ujian diperbarui.';
        } else {
            Ujian::create($payload);
            $this->statusMessage = 'Ujian ditambahkan.';
        }

        $this->errorMessage = null;
        $this->closeModal();
        unset($this->ujian);
    }

    public function delete(int $id): void
    {
        $this->authorize('ujian.manage');

        try {
            Ujian::findOrFail($id)->delete();
            $this->statusMessage = 'Ujian dihapus.';
            $this->errorMessage = null;
        } catch (QueryException) {
            $this->errorMessage = 'Ujian tidak bisa dihapus karena sudah ada peserta yang terdaftar/mengerjakannya.';
        }

        unset($this->ujian);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'bankSoalId', 'nama', 'jumlahSoal', 'sesiMulai', 'sesiSelesai', 'deskripsi']);
        $this->resetErrorBag();
    }
};
