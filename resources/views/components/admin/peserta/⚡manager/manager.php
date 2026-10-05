<?php

use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use App\Support\PasswordPeserta;
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

    #[Url(as: 'pelajaran')]
    public string $filterPelajaranId = '';

    /**
     * Peserta ticked for "Acak Ulang Password". Only ever spans the current
     * page: cleared whenever the page changes (updatedPaginators(), which
     * also fires on the resetPage() a search/jenjang change triggers).
     * Checkbox values arrive as strings, hence the mixed element type.
     *
     * @var array<int, int|string>
     */
    public array $selectedIds = [];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterJenjangId', 'filterPelajaranId'], true)) {
            $this->resetPage();
            $this->selectedIds = [];
        }
    }

    public function updatedPaginators(): void
    {
        $this->selectedIds = [];
    }

    #[Computed]
    public function peserta(): LengthAwarePaginator
    {
        return Peserta::query()
            ->with(['jenjang', 'pelajaranLomba'])
            ->filterAdmin($this->search, $this->filterJenjangId, $this->filterPelajaranId)
            ->orderBy('noreg')
            ->paginate(10);
    }

    /**
     * Drives the header checkbox: ticked only when every row on this page is.
     */
    #[Computed]
    public function semuaDiHalamanTerpilih(): bool
    {
        $idsDiHalaman = $this->peserta->pluck('id')->map(fn (int $id) => (string) $id);

        return $idsDiHalaman->isNotEmpty()
            && $idsDiHalaman->diff(array_map('strval', $this->selectedIds))->isEmpty();
    }

    /**
     * Header checkbox: ticks every row on the current page, or clears them
     * all when they already are.
     */
    public function toggleSemuaDiHalaman(): void
    {
        $this->selectedIds = $this->semuaDiHalamanTerpilih
            ? []
            : $this->peserta->pluck('id')->all();

        unset($this->semuaDiHalamanTerpilih);
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
                // Blank on create → generated (App\Support\PasswordPeserta);
                // blank on edit → existing password left alone.
                'password' => ['nullable', 'string', 'min:6'],
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
            $passwordPlain = $data['password'] ?: PasswordPeserta::generate();

            $peserta = Peserta::create([
                'jenjang_id' => $data['jenjangId'],
                'noreg' => $data['noreg'],
                'nama' => $data['nama'],
                'asal_sekolah' => $data['asalSekolah'],
                'password' => Hash::make($passwordPlain),
                'password_plain' => $passwordPlain,
            ]);

            $this->statusMessage = "Peserta ditambahkan. Password: {$passwordPlain}";
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
        $this->selectedIds = array_values(array_filter($this->selectedIds, fn (int|string $selectedId) => (int) $selectedId !== $id));
        unset($this->peserta);
    }

    /**
     * Fills the form's password field with a generated one, so the admin
     * can see it (and re-roll it) before saving.
     */
    public function acakPassword(): void
    {
        $this->password = PasswordPeserta::generate();
        $this->resetErrorBag('password');
    }

    /**
     * Gives a peserta a fresh generated password. Peserta have no email on
     * file (see App\Models\Peserta), so there's no self-service "forgot
     * password" link to send; this is the admin-side equivalent. Never
     * resets to the noreg — a NISN isn't secret, so that'd let anyone log
     * in as anyone.
     */
    public function resetPassword(int $id): void
    {
        $peserta = Peserta::findOrFail($id);
        $this->gantiPassword($peserta);

        $this->statusMessage = "Password {$peserta->nama} direset menjadi {$peserta->password_plain}.";
        $this->errorMessage = null;
        unset($this->peserta);
    }

    /**
     * "Acak Ulang Password" — regenerates the password of exactly the
     * peserta ticked in the list ($selectedIds), nobody else. Ids that no
     * longer exist are simply skipped.
     */
    public function acakUlangPasswordMassal(): void
    {
        $pesertaTerpilih = Peserta::query()
            ->whereIn('id', array_map('intval', $this->selectedIds))
            ->get();

        if ($pesertaTerpilih->isEmpty()) {
            $this->errorMessage = 'Centang dulu peserta yang password-nya mau diacak ulang.';
            $this->statusMessage = null;

            return;
        }

        $pesertaTerpilih->each(fn (Peserta $peserta) => $this->gantiPassword($peserta));

        // $selectedIds deliberately kept, so "Export Terpilih" can print
        // exactly these peserta's new credentials right away.
        $this->statusMessage = "Password {$pesertaTerpilih->count()} peserta berhasil diacak ulang. Klik Export Terpilih untuk mencetak daftarnya.";
        $this->errorMessage = null;
        unset($this->peserta);
    }

    private function gantiPassword(Peserta $peserta): void
    {
        $passwordPlain = PasswordPeserta::generate();
        $peserta->update(['password' => Hash::make($passwordPlain), 'password_plain' => $passwordPlain]);
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
