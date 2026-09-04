<?php

use App\Models\Siswa;
use App\Models\SiswaUjian;
use App\Models\Ujian;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function totalPeserta(): int
    {
        return Siswa::query()->count();
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
        $rata = SiswaUjian::query()->whereNotNull('nilai')->avg('nilai');

        return $rata !== null ? round((float) $rata, 2) : null;
    }
};
