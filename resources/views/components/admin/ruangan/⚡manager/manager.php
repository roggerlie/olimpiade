<?php

use App\Models\Ruangan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $editingId = null;

    public string $nama = '';

    public ?int $kapasitas = null;

    public ?string $keterangan = null;

    public bool $showModal = false;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    #[Computed]
    public function ruangan(): Collection
    {
        return Ruangan::query()->orderBy('nama')->get();
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

        $ruangan = Ruangan::findOrFail($id);

        $this->editingId = $ruangan->id;
        $this->nama = $ruangan->nama;
        $this->kapasitas = $ruangan->kapasitas;
        $this->keterangan = $ruangan->keterangan;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('master-data.manage');

        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('ruangan', 'nama')->ignore($this->editingId)],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->editingId) {
            Ruangan::findOrFail($this->editingId)->update($data);
            $this->statusMessage = 'Ruangan diperbarui.';
        } else {
            Ruangan::create($data);
            $this->statusMessage = 'Ruangan ditambahkan.';
        }

        $this->errorMessage = null;
        $this->closeModal();
        unset($this->ruangan);
    }

    public function delete(int $id): void
    {
        $this->authorize('master-data.manage');

        try {
            Ruangan::findOrFail($id)->delete();
            $this->statusMessage = 'Ruangan dihapus.';
            $this->errorMessage = null;
        } catch (QueryException) {
            $this->errorMessage = 'Ruangan tidak bisa dihapus karena masih dipakai peserta di suatu ujian. Pindahkan dulu pesertanya ke ruangan lain.';
        }

        unset($this->ruangan);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nama', 'kapasitas', 'keterangan']);
        $this->resetErrorBag();
    }
};
