<?php

use App\Imports\SoalImport;
use App\Imports\SoalWordImport;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component
{
    use WithFileUploads;

    public int $bankSoalId;

    public bool $showModal = false;

    public $file = null;

    public ?int $imported = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    public function mount(int $bankSoalId): void
    {
        $this->bankSoalId = $bankSoalId;
    }

    /**
     * Drives the dropzone's "selected file" display. Server-tracked (not
     * client Alpine state) so it correctly clears when open() resets $file.
     */
    #[Computed]
    public function fileName(): ?string
    {
        return $this->file?->getClientOriginalName();
    }

    #[On('open-soal-import-modal')]
    public function open(): void
    {
        $this->reset(['file', 'imported', 'importErrors']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /**
     * One dropzone, two formats: which importer runs depends purely on the
     * uploaded file's extension — .docx carries gambar (see SoalWordImport's
     * own docblock for why it reads the raw XML instead of a library reader),
     * everything else is the plain text-only Excel/CSV path.
     */
    public function import(): void
    {
        $this->authorize('bank-soal.manage');

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,docx', 'max:5120'],
        ]);

        $ekstensi = strtolower($this->file->getClientOriginalExtension());

        if ($ekstensi === 'docx') {
            $importer = new SoalWordImport($this->bankSoalId);
            $importer->import($this->file->getRealPath());
        } else {
            $importer = new SoalImport($this->bankSoalId);
            Excel::import($importer, $this->file->getRealPath());
        }

        $this->imported = $importer->imported;
        $this->importErrors = $importer->errors;
        $this->file = null;

        if ($importer->imported > 0) {
            $this->dispatch('soal-imported')->to('admin.soal.manager');
        }
    }
};
