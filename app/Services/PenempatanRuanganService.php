<?php

namespace App\Services;

use App\Models\PesertaUjian;
use App\Models\Ruangan;
use App\Models\Ujian;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Room occupancy and automatic placement for one ujian's registrations
 * (PesertaUjian::ruangan()).
 *
 * Occupancy counts not just this ujian's peserta but also those of every
 * *other* ujian whose session window overlaps it in the same room ("bentrok")
 * — two ujian sharing a lab at the same hour share its seats. Kapasitas is
 * advisory: going over it is reported, never refused.
 */
class PenempatanRuanganService
{
    public const URUTAN_CAMPUR_SEKOLAH = 'campur-sekolah';

    public const URUTAN_NOREG = 'noreg';

    /**
     * Occupancy of every room, as seen from $ujian.
     *
     * @return Collection<int, array{ruangan: Ruangan, terisi: int, terisiUjianLain: int, ujianLain: list<string>, total: int, sisa: int, melebihi: bool}>
     */
    public function pemakaian(Ujian $ujian): Collection
    {
        $terisi = PesertaUjian::query()
            ->where('ujian_id', $ujian->id)
            ->whereNotNull('ruangan_id')
            ->selectRaw('ruangan_id, count(*) as jumlah')
            ->groupBy('ruangan_id')
            ->pluck('jumlah', 'ruangan_id');

        $bentrok = PesertaUjian::query()
            ->whereNotNull('ruangan_id')
            ->whereIn('ujian_id', $this->ujianBentrok($ujian)->pluck('id'))
            ->with('ujian:id,nama')
            ->get(['id', 'ujian_id', 'ruangan_id'])
            ->groupBy('ruangan_id');

        return Ruangan::query()->orderBy('nama')->get()->map(function (Ruangan $ruangan) use ($terisi, $bentrok): array {
            $lain = $bentrok->get($ruangan->id, collect());
            $total = (int) ($terisi[$ruangan->id] ?? 0) + $lain->count();

            return [
                'ruangan' => $ruangan,
                'terisi' => (int) ($terisi[$ruangan->id] ?? 0),
                'terisiUjianLain' => $lain->count(),
                'ujianLain' => $lain->pluck('ujian.nama')->unique()->values()->all(),
                'total' => $total,
                'sisa' => max(0, $ruangan->kapasitas - $total),
                'melebihi' => $total > $ruangan->kapasitas,
            ];
        });
    }

    /**
     * "Bagi Otomatis": places every registration of $ujian that has no room
     * yet into the chosen rooms, filling each (in name order) up to its
     * remaining kapasitas. Never overfills — whoever doesn't fit stays
     * unplaced and is reported back. Already-placed peserta aren't moved.
     *
     * @param  array<int, int|string>  $ruanganIds
     * @return array{ditempatkan: int, tidakKebagian: int}
     */
    public function bagiOtomatis(Ujian $ujian, array $ruanganIds, string $urutan): array
    {
        $antrian = $this->urutkan(
            PesertaUjian::query()
                ->where('ujian_id', $ujian->id)
                ->whereNull('ruangan_id')
                ->with('peserta:id,noreg,nama,asal_sekolah')
                ->get(),
            $urutan,
        );

        $sisaPerRuangan = $this->pemakaian($ujian)
            ->filter(fn (array $p) => in_array($p['ruangan']->id, array_map('intval', $ruanganIds), true))
            ->mapWithKeys(fn (array $p) => [$p['ruangan']->id => $p['sisa']]);

        /** @var array<int, list<int>> $penempatan ruangan_id => peserta_ujian ids */
        $penempatan = [];

        foreach ($sisaPerRuangan as $ruanganId => $sisa) {
            $penempatan[$ruanganId] = $antrian->splice(0, $sisa)->pluck('id')->all();
        }

        DB::transaction(function () use ($penempatan): void {
            foreach ($penempatan as $ruanganId => $ids) {
                PesertaUjian::query()->whereIn('id', $ids)->update(['ruangan_id' => $ruanganId]);
            }
        });

        return [
            'ditempatkan' => collect($penempatan)->sum(fn (array $ids) => count($ids)),
            'tidakKebagian' => $antrian->count(),
        ];
    }

    /**
     * Other ujian whose session window overlaps $ujian's.
     *
     * @return Collection<int, Ujian>
     */
    public function ujianBentrok(Ujian $ujian): Collection
    {
        return Ujian::query()
            ->whereKeyNot($ujian->id)
            ->where('sesi_mulai', '<', $ujian->sesi_selesai)
            ->where('sesi_selesai', '>', $ujian->sesi_mulai)
            ->get(['id', 'nama']);
    }

    /**
     * URUTAN_NOREG: by No. Registrasi. URUTAN_CAMPUR_SEKOLAH: round-robin
     * across asal sekolah (largest school first), so consecutive seats —
     * and therefore neighbours in a room — come from different schools
     * wherever the numbers allow.
     *
     * @param  Collection<int, PesertaUjian>  $pesertaUjian
     * @return Collection<int, PesertaUjian>
     */
    private function urutkan(Collection $pesertaUjian, string $urutan): Collection
    {
        if ($urutan !== self::URUTAN_CAMPUR_SEKOLAH) {
            return $pesertaUjian->sortBy('peserta.noreg')->values();
        }

        $perSekolah = $pesertaUjian
            ->groupBy(fn (PesertaUjian $pu) => Str::lower(trim($pu->peserta->asal_sekolah)))
            ->map(fn (Collection $kelompok) => $kelompok->sortBy('peserta.nama')->values())
            ->sortBy([fn (Collection $a, Collection $b) => $b->count() <=> $a->count()])
            ->values();

        $hasil = collect();

        for ($i = 0; $i < ($perSekolah->first()?->count() ?? 0); $i++) {
            foreach ($perSekolah as $kelompok) {
                if ($kelompok->has($i)) {
                    $hasil->push($kelompok[$i]);
                }
            }
        }

        return $hasil;
    }
}
