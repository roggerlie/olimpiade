<?php

use App\Models\Peserta;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function totalPeserta(): int
    {
        return Peserta::query()->count();
    }

    #[Computed]
    public function ujianBerlangsung(): int
    {
        return Ujian::query()
            ->where('sesi_mulai', '<=', now())
            ->where('sesi_selesai', '>=', now())
            ->count();
    }

    #[Computed]
    public function rataRataNilai(): ?float
    {
        $rata = PesertaUjian::query()->whereNotNull('nilai')->avg('nilai');

        return $rata !== null ? round((float) $rata, 2) : null;
    }
};
