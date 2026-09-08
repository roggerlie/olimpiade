<?php

use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $editingId = null;

    public ?int $jenjangId = null;

    public ?int $pelajaranId = null;

    public string $nama = '';

    public ?string $deskripsi = null;

    public bool $showModal = false;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    #[Computed]
    public function bankSoal(): Collection
    {
        return BankSoal::query()
            ->with(['jenjang', 'pelajaran'])
            ->withCount('soal')
            ->orderBy('nama')
            ->get();
    }

    #[Computed]
    public function jenjangPilihan(): Collection
    {
        return Jenjang::query()->orderBy('id')->get();
    }

    #[Computed]
    public function pelajaranPilihan(): Collection
    {
        return Pelajaran::query()->orderBy('nama')->get();
    }

    public function create(): void
    {
        $this->authorize('bank-soal.manage');

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('bank-soal.manage');

        $bankSoal = BankSoal::findOrFail($id);

        $this->editingId = $bankSoal->id;
        $this->jenjangId = $bankSoal->jenjang_id;
        $this->pelajaranId = $bankSoal->pelajaran_id;
        $this->nama = $bankSoal->nama;
        $this->deskripsi = $bankSoal->deskripsi;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('bank-soal.manage');

        $data = $this->validate([
            'jenjangId' => ['required', 'exists:jenjang,id'],
            'pelajaranId' => ['required', 'exists:pelajaran,id'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $payload = [
            'jenjang_id' => $data['jenjangId'],
            'pelajaran_id' => $data['pelajaranId'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'],
        ];

        if ($this->editingId) {
            BankSoal::findOrFail($this->editingId)->update($payload);
            $this->statusMessage = 'Bank soal diperbarui.';
        } else {
            BankSoal::create($payload);
            $this->statusMessage = 'Bank soal ditambahkan.';
        }

        $this->errorMessage = null;
        $this->closeModal();
        unset($this->bankSoal);
    }

    public function delete(int $id): void
    {
        $this->authorize('bank-soal.manage');

        try {
            BankSoal::findOrFail($id)->delete();
            $this->statusMessage = 'Bank soal dihapus.';
            $this->errorMessage = null;
        } catch (QueryException) {
            $this->errorMessage = 'Bank soal tidak bisa dihapus karena masih dipakai oleh ujian.';
        }

        unset($this->bankSoal);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'jenjangId', 'pelajaranId', 'nama', 'deskripsi']);
        $this->resetErrorBag();
    }
};
