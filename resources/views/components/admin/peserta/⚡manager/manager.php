<?php

use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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

    /** @var array<int, int> */
    public array $pelajaranLombaIds = [];

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
            ->with(['jenjang', 'pelajaranLomba'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('noreg', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterJenjangId, fn ($query) => $query->where('jenjang_id', $this->filterJenjangId))
            ->orderBy('noreg')
            ->paginate(10);
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
        $this->pelajaranLombaIds = $peserta->pelajaranLomba->pluck('id')->all();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = Validator::make(
            [
                'noreg' => $this->noreg,
                'nama' => $this->nama,
                'jenjangId' => $this->jenjangId,
                'asalSekolah' => $this->asalSekolah,
                'password' => $this->password,
            ],
            [
                // noreg is a peserta's NISN (10 digits) and their login id
                // (see App\Models\Peserta).
                'noreg' => ['required', 'string', 'size:10', Rule::unique('peserta', 'noreg')->ignore($this->editingId)],
                'nama' => ['required', 'string', 'max:255'],
                'jenjangId' => ['required', 'exists:jenjang,id'],
                'asalSekolah' => ['required', 'string', 'max:255'],
                'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:6'],
            ]
        )->validate();

        if ($this->editingId) {
            $peserta = Peserta::findOrFail($this->editingId);

            // jenjang_id/noreg/nama/asal_sekolah are all `required` above, so
            // never falsy — array_filter() here only ever drops `password`/
            // `password_plain` when left blank (leave the existing one alone).
            $peserta->update(array_filter([
                'jenjang_id' => $data['jenjangId'],
                'noreg' => $data['noreg'],
                'nama' => $data['nama'],
                'asal_sekolah' => $data['asalSekolah'],
                'password' => $data['password'] ? Hash::make($data['password']) : null,
                'password_plain' => $data['password'] ?: null,
            ]));

            $this->statusMessage = 'Peserta diperbarui.';
        } else {
            $peserta = Peserta::create([
                'jenjang_id' => $data['jenjangId'],
                'noreg' => $data['noreg'],
                'nama' => $data['nama'],
                'asal_sekolah' => $data['asalSekolah'],
                'password' => Hash::make($data['password']),
                'password_plain' => $data['password'],
            ]);

            $this->statusMessage = 'Peserta ditambahkan.';
        }

        // Minat lomba (App\Models\Peserta::pelajaranLomba()) — sync() so
        // unchecking a mapel here actually drops it, not just adds new ones.
        $peserta->pelajaranLomba()->sync($this->pelajaranLombaIds);

        $this->errorMessage = null;
        $this->closeModal();
        unset($this->peserta);
    }

    public function delete(int $id): void
    {
        $peserta = Peserta::findOrFail($id);
        // Cascades to peserta_ujian and peserta_soal (see database/migrations).
        $peserta->delete();

        $this->statusMessage = 'Peserta dihapus.';
        $this->errorMessage = null;
        unset($this->peserta);
    }

    /**
     * Resets a peserta's password back to their own noreg — the same
     * fallback App\Imports\PesertaImport uses when a sheet's password
     * column is left blank. Peserta have no email on file (see
     * App\Models\Peserta), so there's no self-service "forgot password"
     * link to send; this is the admin-side equivalent.
     */
    public function resetPassword(int $id): void
    {
        $peserta = Peserta::findOrFail($id);
        $peserta->update(['password' => Hash::make($peserta->noreg), 'password_plain' => $peserta->noreg]);

        $this->statusMessage = "Password {$peserta->nama} direset ke No. Registrasi-nya ({$peserta->noreg}).";
        $this->errorMessage = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'noreg', 'nama', 'jenjangId', 'asalSekolah', 'password', 'pelajaranLombaIds']);
        $this->resetErrorBag();
    }
};
