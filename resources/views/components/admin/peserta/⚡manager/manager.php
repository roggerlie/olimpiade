<?php

use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $noreg = '';

    public string $nama = '';

    public ?int $jenjangId = null;

    public string $asalSekolah = '';

    public string $password = '';

    public bool $showModal = false;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'jenjang')]
    public string $filterJenjangId = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterJenjangId'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function peserta(): LengthAwarePaginator
    {
        return Peserta::query()
            ->with('jenjang')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('noreg', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterJenjangId, fn ($query) => $query->where('jenjang_id', $this->filterJenjangId))
            ->orderBy('nama')
            ->paginate(10);
    }

    #[Computed]
    public function jenjangPilihan(): Collection
    {
        return Jenjang::query()->orderBy('nama')->get();
    }

    #[On('peserta-imported')]
    public function refreshList(): void
    {
        unset($this->peserta);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $peserta = Peserta::findOrFail($id);

        $this->editingId = $peserta->id;
        $this->noreg = $peserta->noreg;
        $this->nama = $peserta->nama;
        $this->jenjangId = $peserta->jenjang_id;
        $this->asalSekolah = $peserta->asal_sekolah;
        $this->password = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $usernameUnique = Rule::unique('users', 'username')->ignore($this->editingId ? Peserta::find($this->editingId)?->user_id : null);
        $noregUnique = Rule::unique('peserta', 'noreg')->ignore($this->editingId);

        $data = Validator::make(
            [
                'noreg' => $this->noreg,
                'nama' => $this->nama,
                'jenjangId' => $this->jenjangId,
                'asalSekolah' => $this->asalSekolah,
                'password' => $this->password,
            ],
            [
                'noreg' => ['required', 'string', 'size:7', $usernameUnique, $noregUnique],
                'nama' => ['required', 'string', 'max:255'],
                'jenjangId' => ['required', 'exists:jenjang,id'],
                'asalSekolah' => ['required', 'string', 'max:255'],
                'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:6'],
            ]
        )->validate();

        if ($this->editingId) {
            $peserta = Peserta::findOrFail($this->editingId);

            DB::transaction(function () use ($peserta, $data): void {
                $peserta->update([
                    'jenjang_id' => $data['jenjangId'],
                    'noreg' => $data['noreg'],
                    'nama' => $data['nama'],
                    'asal_sekolah' => $data['asalSekolah'],
                ]);

                $peserta->user->update(array_filter([
                    'username' => $data['noreg'],
                    'name' => $data['nama'],
                    'password' => $data['password'] ? Hash::make($data['password']) : null,
                ]));
            });

            $this->statusMessage = 'Peserta diperbarui.';
        } else {
            DB::transaction(function () use ($data): void {
                $user = User::create([
                    'username' => $data['noreg'],
                    'name' => $data['nama'],
                    'password' => Hash::make($data['password']),
                ]);
                $user->assignRole('peserta');

                Peserta::create([
                    'user_id' => $user->id,
                    'jenjang_id' => $data['jenjangId'],
                    'noreg' => $data['noreg'],
                    'nama' => $data['nama'],
                    'asal_sekolah' => $data['asalSekolah'],
                ]);
            });

            $this->statusMessage = 'Peserta ditambahkan.';
        }

        $this->errorMessage = null;
        $this->closeModal();
        unset($this->peserta);
    }

    public function delete(int $id): void
    {
        $peserta = Peserta::findOrFail($id);
        // Deleting the parent User cascades to peserta, peserta_ujian and
        // peserta_soal (see database/migrations) — one call, no orphaned login.
        $peserta->user->delete();

        $this->statusMessage = 'Peserta dihapus.';
        $this->errorMessage = null;
        unset($this->peserta);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'noreg', 'nama', 'jenjangId', 'asalSekolah', 'password']);
        $this->resetErrorBag();
    }
};
