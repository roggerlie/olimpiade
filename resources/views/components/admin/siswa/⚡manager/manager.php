<?php

use App\Models\Jenjang;
use App\Models\Siswa;
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
    public function siswa(): LengthAwarePaginator
    {
        return Siswa::query()
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

    #[On('siswa-imported')]
    public function refreshList(): void
    {
        unset($this->siswa);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $siswa = Siswa::findOrFail($id);

        $this->editingId = $siswa->id;
        $this->noreg = $siswa->noreg;
        $this->nama = $siswa->nama;
        $this->jenjangId = $siswa->jenjang_id;
        $this->asalSekolah = $siswa->asal_sekolah;
        $this->password = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $usernameUnique = Rule::unique('users', 'username')->ignore($this->editingId ? Siswa::find($this->editingId)?->user_id : null);
        $noregUnique = Rule::unique('siswa', 'noreg')->ignore($this->editingId);

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
            $siswa = Siswa::findOrFail($this->editingId);

            DB::transaction(function () use ($siswa, $data): void {
                $siswa->update([
                    'jenjang_id' => $data['jenjangId'],
                    'noreg' => $data['noreg'],
                    'nama' => $data['nama'],
                    'asal_sekolah' => $data['asalSekolah'],
                ]);

                $siswa->user->update(array_filter([
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
                $user->assignRole('siswa');

                Siswa::create([
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
        unset($this->siswa);
    }

    public function delete(int $id): void
    {
        $siswa = Siswa::findOrFail($id);
        // Deleting the parent User cascades to siswa, siswa_ujian and
        // siswa_soal (see database/migrations) — one call, no orphaned login.
        $siswa->user->delete();

        $this->statusMessage = 'Peserta dihapus.';
        $this->errorMessage = null;
        unset($this->siswa);
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
