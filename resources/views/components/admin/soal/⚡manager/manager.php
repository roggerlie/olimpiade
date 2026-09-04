<?php

use App\Models\Soal;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $bankSoalId;

    public ?int $editingId = null;

    public string $pertanyaan = '';

    public string $pilihA = '';

    public string $pilihB = '';

    public string $pilihC = '';

    public string $pilihD = '';

    public ?string $pilihE = null;

    public string $jawaban = '';

    public bool $showModal = false;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(int $bankSoalId): void
    {
        $this->bankSoalId = $bankSoalId;
    }

    #[Computed]
    public function soal(): LengthAwarePaginator
    {
        return Soal::query()
            ->where('bank_soal_id', $this->bankSoalId)
            ->orderBy('id')
            ->paginate(10);
    }

    protected function rules(): array
    {
        return [
            'pertanyaan' => ['required', 'string'],
            'pilihA' => ['required', 'string'],
            'pilihB' => ['required', 'string'],
            'pilihC' => ['required', 'string'],
            'pilihD' => ['required', 'string'],
            'pilihE' => ['nullable', 'string'],
            'jawaban' => ['required', 'in:A,B,C,D,E'],
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    /**
     * Marks one pilihan as the correct answer — called by clicking its
     * letter badge in the form, replacing the old disconnected "Jawaban
     * Benar" dropdown so marking an answer can't drift from the option
     * it's actually next to.
     */
    public function tandaiJawaban(string $huruf): void
    {
        $this->jawaban = $huruf;
    }

    public function edit(int $id): void
    {
        $soal = Soal::findOrFail($id);

        $this->editingId = $soal->id;
        $this->pertanyaan = $soal->pertanyaan;
        $this->pilihA = $soal->pilih_a;
        $this->pilihB = $soal->pilih_b;
        $this->pilihC = $soal->pilih_c;
        $this->pilihD = $soal->pilih_d;
        $this->pilihE = $soal->pilih_e;
        $this->jawaban = $soal->jawaban;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = Validator::make(
            [
                'pertanyaan' => $this->pertanyaan,
                'pilihA' => $this->pilihA,
                'pilihB' => $this->pilihB,
                'pilihC' => $this->pilihC,
                'pilihD' => $this->pilihD,
                'pilihE' => $this->pilihE,
                'jawaban' => $this->jawaban,
            ],
            $this->rules()
        )->after(function ($validator) {
            if ($this->jawaban === 'E' && blank($this->pilihE)) {
                $validator->errors()->add('jawaban', 'Jawaban E dipilih tapi pilihan E belum diisi.');
            }
        })->validate();

        $payload = [
            'bank_soal_id' => $this->bankSoalId,
            'pertanyaan' => $data['pertanyaan'],
            'pilih_a' => $data['pilihA'],
            'pilih_b' => $data['pilihB'],
            'pilih_c' => $data['pilihC'],
            'pilih_d' => $data['pilihD'],
            'pilih_e' => $data['pilihE'],
            'jawaban' => $data['jawaban'],
        ];

        if ($this->editingId) {
            Soal::findOrFail($this->editingId)->update($payload);
            $this->statusMessage = 'Soal diperbarui.';
        } else {
            Soal::create($payload);
            $this->statusMessage = 'Soal ditambahkan.';
        }

        $this->errorMessage = null;
        $this->closeModal();
        unset($this->soal);
    }

    public function delete(int $id): void
    {
        try {
            Soal::findOrFail($id)->delete();
            $this->statusMessage = 'Soal dihapus.';
            $this->errorMessage = null;
        } catch (QueryException) {
            $this->errorMessage = 'Soal tidak bisa dihapus karena sudah dijawab oleh peserta pada suatu ujian.';
        }

        unset($this->soal);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'pertanyaan', 'pilihA', 'pilihB', 'pilihC', 'pilihD', 'pilihE', 'jawaban']);
        $this->resetErrorBag();
    }
};
