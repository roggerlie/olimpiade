<?php

use App\Services\DeteksiLoginGandaService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Log Aktivitas → Deteksi Login Ganda: peserta logins worth a second look
 * (possible "joki"), see App\Services\DeteksiLoginGandaService.
 */
new class extends Component
{
    public string $dari = '';

    public string $sampai = '';

    public function mount(): void
    {
        $this->authorize('log-aktivitas.view');

        $this->dari = now()->toDateString();
        $this->sampai = now()->toDateString();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rentang(): array
    {
        $valid = fn (string $tanggal) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) && strtotime($tanggal);

        return [
            $valid($this->dari) ? $this->dari : now()->toDateString(),
            $valid($this->sampai) ? $this->sampai : now()->toDateString(),
        ];
    }

    #[Computed]
    public function pesertaBanyakPerangkat(): Collection
    {
        return app(DeteksiLoginGandaService::class)->pesertaBanyakPerangkat(...$this->rentang());
    }

    #[Computed]
    public function perangkatBanyakPeserta(): Collection
    {
        return app(DeteksiLoginGandaService::class)->perangkatBanyakPeserta(...$this->rentang());
    }
};
