<?php

use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Support\Collection;
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

    /**
     * Feeds the "Peserta per Jenjang" bar chart — one bar per jenjang, even
     * when it has zero peserta, so the chart never silently drops a level.
     *
     * @return Collection<int, array{label: string, total: int}>
     */
    #[Computed]
    public function pesertaPerJenjang(): Collection
    {
        return Jenjang::query()
            ->withCount('peserta')
            ->orderBy('nama')
            ->get()
            ->map(fn (Jenjang $jenjang) => [
                'label' => $jenjang->nama,
                'total' => $jenjang->peserta_count,
            ]);
    }

    /**
     * Feeds the "Rata-rata Nilai per Ujian" bar chart — only ujian with at
     * least one scored attempt, highest average first, capped at 8 bars so
     * the chart stays readable as more ujian pile up.
     *
     * @return Collection<int, array{label: string, total: float}>
     */
    #[Computed]
    public function rataNilaiPerUjian(): Collection
    {
        return PesertaUjian::query()
            ->whereNotNull('nilai')
            ->selectRaw('ujian_id, avg(nilai) as rata_nilai')
            ->groupBy('ujian_id')
            ->with('ujian:id,nama')
            ->orderByDesc('rata_nilai')
            ->limit(8)
            ->get()
            ->map(fn (PesertaUjian $row) => [
                'label' => $row->ujian->nama,
                'total' => round((float) $row->rata_nilai, 1),
            ]);
    }
};
