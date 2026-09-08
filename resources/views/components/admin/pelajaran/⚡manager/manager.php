<?php

use App\Models\Pelajaran;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $editingId = null;

    public string $nama = '';

    public bool $showModal = false;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    #[Computed]
    public function pelajaran(): Collection
    {
        return Pelajaran::query()->orderBy('nama')->get();
    }

    public function create(): void
    {
        $this->authorize('master-data.manage');

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('master-data.manage');

        $pelajaran = Pelajaran::findOrFail($id);

        $this->editingId = $pelajaran->id;
        $this->nama = $pelajaran->nama;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('master-data.manage');

        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        if ($this->editingId) {
            Pelajaran::findOrFail($this->editingId)->update($data);
            $this->statusMessage = 'Pelajaran diperbarui.';
        } else {
            Pelajaran::create($data);
            $this->statusMessage = 'Pelajaran ditambahkan.';
        }

        $this->errorMessage = null;
        $this->closeModal();
        unset($this->pelajaran);
    }

    public function delete(int $id): void
    {
        $this->authorize('master-data.manage');

        try {
            Pelajaran::findOrFail($id)->delete();
            $this->statusMessage = 'Pelajaran dihapus.';
            $this->errorMessage = null;
        } catch (QueryException) {
            $this->errorMessage = 'Pelajaran tidak bisa dihapus karena masih dipakai oleh bank soal atau ujian.';
        }

        unset($this->pelajaran);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nama']);
        $this->resetErrorBag();
    }
};
