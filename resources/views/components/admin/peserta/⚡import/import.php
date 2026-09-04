<?php

use App\Imports\PesertaImport;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component
{
    use WithFileUploads;

    public bool $showModal = false;

    public $file = null;

    public ?int $imported = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    #[On('open-import-modal')]
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

    public function import(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $importer = new PesertaImport;
        Excel::import($importer, $this->file->getRealPath());

        $this->imported = $importer->imported;
        $this->importErrors = $importer->errors;
        $this->file = null;

        if ($importer->imported > 0) {
            $this->dispatch('peserta-imported')->to('admin.peserta.manager');
        }
    }
};
