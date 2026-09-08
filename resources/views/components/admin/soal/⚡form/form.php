<?php

use App\Models\Soal;
use App\Support\SoalContentPurifier;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

/**
 * Tambah/Ubah Soal used to be a modal on admin.soal.manager's own list page;
 * it's its own page now (see routes/admin.php's bank-soal.soal.create /
 * bank-soal.soal.edit) — the six TinyMCE editors (Pertanyaan + Pilihan A-E)
 * need real room, which a modal's max-h-[85vh] scroll area kept fighting.
 * One component serves both routes: `soal` is null for create, bound for
 * edit (see mount()).
 */
new class extends Component
{
    public int $bankSoalId;

    public ?int $soalId = null;

    public string $pertanyaan = '';

    public string $pilihA = '';

    public string $pilihB = '';

    public string $pilihC = '';

    public string $pilihD = '';

    public ?string $pilihE = null;

    public string $jawaban = '';

    public function mount(int $bankSoalId, ?Soal $soal = null): void
    {
        $this->authorize('bank-soal.manage');

        $this->bankSoalId = $bankSoalId;

        if ($soal) {
            $this->soalId = $soal->id;
            $this->pertanyaan = $soal->pertanyaan;
            $this->pilihA = $soal->pilih_a ?? '';
            $this->pilihB = $soal->pilih_b ?? '';
            $this->pilihC = $soal->pilih_c ?? '';
            $this->pilihD = $soal->pilih_d ?? '';
            $this->pilihE = $soal->pilih_e;
            $this->jawaban = $soal->jawaban;
        }
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

    protected function rules(): array
    {
        return [
            'pertanyaan' => ['required', 'string'],
            'pilihA' => ['nullable', 'string'],
            'pilihB' => ['nullable', 'string'],
            'pilihC' => ['nullable', 'string'],
            'pilihD' => ['nullable', 'string'],
            'pilihE' => ['nullable', 'string'],
            'jawaban' => ['required', 'in:A,B,C,D,E'],
        ];
    }

    public function save(): void
    {
        $this->authorize('bank-soal.manage');

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
            if (SoalContentPurifier::kosong($this->pertanyaan)) {
                $validator->errors()->add('pertanyaan', 'Pertanyaan harus diisi teks atau gambar.');
            }

            $wajib = ['A' => $this->pilihA, 'B' => $this->pilihB, 'C' => $this->pilihC, 'D' => $this->pilihD];

            foreach ($wajib as $huruf => $html) {
                if (SoalContentPurifier::kosong($html)) {
                    $validator->errors()->add("pilih{$huruf}", "Pilihan {$huruf} harus diisi teks atau gambar.");
                }
            }

            if ($this->jawaban === 'E' && SoalContentPurifier::kosong($this->pilihE)) {
                $validator->errors()->add('jawaban', 'Jawaban E dipilih tapi pilihan E belum diisi (teks atau gambar).');
            }
        })->validate();

        $payload = [
            'bank_soal_id' => $this->bankSoalId,
            'pertanyaan' => SoalContentPurifier::bersihkan($data['pertanyaan']),
            'pilih_a' => SoalContentPurifier::bersihkan($data['pilihA']),
            'pilih_b' => SoalContentPurifier::bersihkan($data['pilihB']),
            'pilih_c' => SoalContentPurifier::bersihkan($data['pilihC']),
            'pilih_d' => SoalContentPurifier::bersihkan($data['pilihD']),
            'pilih_e' => SoalContentPurifier::kosong($data['pilihE']) ? null : SoalContentPurifier::bersihkan($data['pilihE']),
            'jawaban' => $data['jawaban'],
        ];

        if ($this->soalId) {
            Soal::findOrFail($this->soalId)->update($payload);
            $pesan = 'Soal diperbarui.';
        } else {
            Soal::create($payload);
            $pesan = 'Soal ditambahkan.';
        }

        session()->flash('status', $pesan);
        $this->redirect(route('admin.bank-soal.soal', $this->bankSoalId), navigate: false);
    }
};
