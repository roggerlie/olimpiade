<?php

use App\Models\Jenjang;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $editingId = null;

    public string $kode = '';

    public string $nama = '';

    public bool $showModal = false;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    #[Computed]
    public function jenjang(): Collection
    {
        return Jenjang::query()->orderBy('nama')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $jenjang = Jenjang::findOrFail($id);

        $this->editingId = $jenjang->id;
        $this->kode = $jenjang->kode;
        $this->nama = $jenjang->nama;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'kode' => ['required', 'string', 'size:2', Rule::unique('jenjang', 'kode')->ignore($this->editingId)],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        if ($this->editingId) {
            Jenjang::findOrFail($this->editingId)->update($data);
            $this->statusMessage = 'Jenjang diperbarui.';
        } else {
            Jenjang::create($data);
            $this->statusMessage = 'Jenjang ditambahkan.';
        }

        $this->errorMessage = null;
        $this->closeModal();
        unset($this->jenjang);
    }

    public function delete(int $id): void
    {
        try {
            Jenjang::findOrFail($id)->delete();
            $this->statusMessage = 'Jenjang dihapus.';
            $this->errorMessage = null;
        } catch (QueryException) {
            $this->errorMessage = 'Jenjang tidak bisa dihapus karena masih dipakai oleh bank soal, ujian, atau peserta.';
        }

        unset($this->jenjang);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'kode', 'nama']);
        $this->resetErrorBag();
    }
};
