<?php

namespace Database\Seeders;

use App\Models\BankSoal;
use App\Models\Soal;
use App\Support\SoalContentPurifier;
use Database\Seeders\Gambar\IpaSdGambar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

/**
 * 50 illustrated IPA SD soal (termometer, grafik, rangkaian listrik, gelas
 * ukur, rantai makanan, neraca, magnet, perubahan wujud, kecepatan), every one
 * with its own SVG diagram (see Gambar\IpaSdGambar) stored on the public disk
 * the same way an editor upload is: storage/app/public/soal/{bankSoalId}/.
 *
 * Not part of DatabaseSeeder — run it on its own and pick the target bank
 * soal from the list it shows (an IPA SD one is preselected):
 *
 *   php artisan db:seed --class=SoalIpaSdSeeder
 *
 * Picking a bank soal that isn't SD + IPA is allowed, but only after an
 * explicit "Tetap lanjut?" (defaults to no, so --no-interaction aborts).
 * Code can skip the prompt via $this->callWith(SoalIpaSdSeeder::class,
 * ['bankSoalId' => 5]).
 *
 * Re-runnable: soal it seeded before (recognized by their `seed-ipa-sd-`
 * gambar) are replaced, soal an admin wrote by hand are left alone.
 */
class SoalIpaSdSeeder extends Seeder
{
    private const PREFIX_GAMBAR = 'seed-ipa-sd-';

    private BankSoal $bankSoal;

    private int $nomor = 0;

    public function run(?int $bankSoalId = null): void
    {
        $id = $bankSoalId ?? $this->pilihBankSoal();

        if ($id === null) {
            return;
        }

        $bankSoal = BankSoal::query()
            ->with(['jenjang', 'pelajaran'])
            ->where('id', $id)
            ->first();

        if (! $bankSoal) {
            $this->command?->error("Bank soal dengan id {$id} tidak ditemukan.");

            return;
        }

        if (! $this->bankCocok($bankSoal) && ! $this->konfirmasiBankBeda($bankSoal)) {
            $this->command?->warn('Dibatalkan, tidak ada soal yang ditambahkan.');

            return;
        }

        $this->bankSoal = $bankSoal;

        DB::transaction(function (): void {
            $this->hapusSeedLama();

            $this->termometer();
            $this->grafikTanaman();
            $this->rangkaianListrik();
            $this->gelasUkur();
            $this->rantaiMakanan();
            $this->neraca();
            $this->magnet();
            $this->perubahanWujud();
            $this->kecepatan();
        });

        $this->command?->info("{$this->nomor} soal IPA SD bergambar ditambahkan ke \"{$bankSoal->nama}\" (id {$bankSoal->id}).");
    }

    /**
     * Lists every bank soal and returns the chosen id, or null when there's
     * nothing to choose from.
     */
    private function pilihBankSoal(): ?int
    {
        if (! $this->command) {
            throw new RuntimeException('Tanpa console, panggil dengan callWith(SoalIpaSdSeeder::class, [\'bankSoalId\' => ...]).');
        }

        $semua = BankSoal::query()->with(['jenjang', 'pelajaran'])->orderBy('id')->get();

        if ($semua->isEmpty()) {
            $this->command->error('Belum ada bank soal. Buat dulu di menu Bank Soal.');

            return null;
        }

        return (int) select(
            label: 'Pilih bank soal',
            options: $semua->mapWithKeys(fn (BankSoal $b) => [
                $b->id => "{$b->id} — {$b->nama} ({$b->jenjang?->nama}, {$b->pelajaran?->nama})",
            ])->all(),
            default: $semua->first(fn (BankSoal $b) => $this->bankCocok($b))?->id,
            scroll: 10,
        );
    }

    private function bankCocok(BankSoal $bankSoal): bool
    {
        return $bankSoal->jenjang?->nama === 'SD' && $bankSoal->pelajaran?->nama === 'IPA';
    }

    private function konfirmasiBankBeda(BankSoal $bankSoal): bool
    {
        if (! $this->command) {
            return false;
        }

        $this->command->warn(
            "Bank soal #{$bankSoal->id} \"{$bankSoal->nama}\" adalah {$bankSoal->jenjang?->nama} / {$bankSoal->pelajaran?->nama}, "
            .'sedangkan soal ini untuk SD / IPA.'
        );

        return confirm('Tetap lanjut?', default: false);
    }

    private function termometer(): void
    {
        foreach ([24, 31, 37, 18, 42, 27] as $i => $suhu) {
            $naik = $i >= 4;
            $jawaban = $naik ? $suhu + 5 : $suhu;

            $this->buat(
                'Perhatikan termometer berikut!',
                IpaSdGambar::termometer($suhu),
                "Termometer menunjukkan suhu {$suhu} °C",
                $naik ? 'Jika suhu benda tersebut naik 5 °C, suhunya menjadi ....' : 'Suhu yang ditunjukkan oleh termometer tersebut adalah ....',
                "{$jawaban} °C",
                [($jawaban + 2).' °C', ($jawaban - 3).' °C', ($naik ? $suhu : $jawaban + 5).' °C'],
            );
        }
    }

    private function grafikTanaman(): void
    {
        $data = [[2, 4, 8, 10, 14], [4, 6, 8, 12, 16], [2, 6, 8, 12, 18], [4, 8, 10, 14, 16], [2, 4, 6, 10, 12], [6, 8, 12, 14, 20]];

        foreach ($data as $i => $t) {
            $alt = 'Grafik batang tinggi tanaman kacang hijau dari hari 1 sampai hari 5';

            if ($i % 2 === 0) {
                $this->buat(
                    'Siswa mengukur tinggi tanaman kacang hijau setiap hari. Hasilnya disajikan pada grafik berikut.',
                    IpaSdGambar::grafikTanaman($t), $alt,
                    'Pertambahan tinggi tanaman dari hari ke-1 sampai hari ke-5 adalah ....',
                    ($t[4] - $t[0]).' cm',
                    [$t[4].' cm', ($t[4] - $t[1]).' cm', ($t[4] + $t[0]).' cm'],
                );
            } else {
                $this->buat(
                    'Perhatikan grafik pertumbuhan tanaman kacang hijau berikut!',
                    IpaSdGambar::grafikTanaman($t), $alt,
                    'Tinggi tanaman pada hari ke-3 adalah ....',
                    $t[2].' cm',
                    [$t[1].' cm', $t[3].' cm', ($t[2] + 1).' cm'],
                );
            }
        }
    }

    private function rangkaianListrik(): void
    {
        $soal = [
            ['seri', 2, false, 'Jika lampu L1 putus, yang terjadi pada lampu L2 adalah ....', 'Ikut padam', ['Tetap menyala', 'Menyala lebih terang', 'Berkedip-kedip']],
            ['paralel', 2, false, 'Jika lampu L1 putus, yang terjadi pada lampu L2 adalah ....', 'Tetap menyala', ['Ikut padam', 'Menyala sebentar lalu padam', 'Berkedip-kedip']],
            ['seri', 3, false, 'Rangkaian listrik pada gambar disebut rangkaian ....', 'Seri', ['Paralel', 'Campuran', 'Terbuka']],
            ['paralel', 3, false, 'Rangkaian listrik pada gambar disebut rangkaian ....', 'Paralel', ['Seri', 'Campuran', 'Terbuka']],
            ['paralel', 3, false, 'Jika lampu L2 putus, lampu yang tetap menyala adalah ....', 'L1 dan L3', ['Tidak ada', 'Hanya L1', 'L1, L2, dan L3']],
            ['seri', 2, true, 'Apa yang terjadi pada kedua lampu dalam rangkaian tersebut?', 'Keduanya padam karena rangkaian terbuka', ['Keduanya menyala', 'Hanya L1 yang menyala', 'Hanya L2 yang menyala']],
        ];

        foreach ($soal as [$jenis, $lampu, $terbuka, $tanya, $benar, $salah]) {
            $this->buat(
                'Perhatikan rangkaian listrik berikut!',
                IpaSdGambar::rangkaianListrik($jenis, $lampu, $terbuka),
                "Rangkaian listrik {$jenis} dengan {$lampu} lampu",
                $tanya, $benar, $salah,
            );
        }
    }

    private function gelasUkur(): void
    {
        foreach ([35, 60, 85] as $volume) {
            $this->buat(
                'Perhatikan gelas ukur berikut!',
                IpaSdGambar::gelasUkur($volume),
                'Gelas ukur berisi air',
                'Volume air di dalam gelas ukur tersebut adalah ....',
                "{$volume} mL",
                [($volume - 5).' mL', ($volume + 5).' mL', ($volume + 10).' mL'],
            );
        }

        foreach ([[40, 55], [50, 80], [25, 65]] as [$sebelum, $sesudah]) {
            $batu = $sesudah - $sebelum;

            $this->buat(
                'Sebuah batu dimasukkan ke dalam gelas ukur berisi air seperti pada gambar berikut.',
                IpaSdGambar::gelasUkurDenganBatu($sebelum, $sesudah),
                'Gelas ukur sebelum dan sesudah batu dimasukkan',
                'Volume batu tersebut adalah ....',
                "{$batu} mL",
                ["{$sesudah} mL", "{$sebelum} mL", ($batu + 5).' mL'],
            );
        }
    }

    private function rantaiMakanan(): void
    {
        $soal = [
            [['Padi', 'Tikus', 'Ular', 'Elang'], 'Makhluk hidup yang berperan sebagai konsumen tingkat II adalah ....', 'Ular', ['Padi', 'Tikus', 'Elang']],
            [['Rumput', 'Belalang', 'Katak', 'Ular'], 'Makhluk hidup yang berperan sebagai konsumen tingkat I adalah ....', 'Belalang', ['Rumput', 'Katak', 'Ular']],
            [['Fitoplankton', 'Zooplankton', 'Ikan kecil', 'Ikan besar'], 'Makhluk hidup yang berperan sebagai konsumen puncak adalah ....', 'Ikan besar', ['Fitoplankton', 'Zooplankton', 'Ikan kecil']],
            [['Daun', 'Ulat', 'Burung', 'Elang'], 'Jika populasi burung berkurang drastis, yang terjadi pada populasi ulat adalah ....', 'Meningkat', ['Menurun', 'Tetap', 'Punah']],
            [['Rumput', 'Kambing', 'Harimau'], 'Dalam rantai makanan tersebut, kambing berperan sebagai ....', 'Konsumen tingkat I (herbivora)', ['Produsen', 'Konsumen tingkat II (karnivora)', 'Pengurai']],
            [['Padi', 'Burung pipit', 'Ular', 'Elang'], 'Makhluk hidup yang berperan sebagai produsen adalah ....', 'Padi', ['Burung pipit', 'Ular', 'Elang']],
        ];

        foreach ($soal as [$rantai, $tanya, $benar, $salah]) {
            $this->buat(
                'Perhatikan rantai makanan berikut!',
                IpaSdGambar::rantaiMakanan($rantai),
                'Rantai makanan: '.implode(' → ', $rantai),
                $tanya, $benar, $salah,
            );
        }
    }

    private function neraca(): void
    {
        $soal = [['Apel', [200, 50]], ['Jeruk', [100, 100, 20]], ['Buku', [500, 100]], ['Kue', [100, 50, 20, 10]], ['Kotak', [200, 100, 50]]];

        foreach ($soal as [$benda, $anakTimbangan]) {
            $massa = array_sum($anakTimbangan);

            $this->buat(
                'Perhatikan neraca berikut! Neraca dalam keadaan seimbang.',
                IpaSdGambar::neraca($benda, $anakTimbangan),
                "Neraca seimbang dengan {$benda} di sisi kiri dan anak timbangan di sisi kanan",
                "Massa {$benda} tersebut adalah ....",
                "{$massa} gram",
                [($massa + 50).' gram', ($massa - 30).' gram', ($massa + 100).' gram'],
            );
        }
    }

    private function magnet(): void
    {
        $akibat = ['Tarik-menarik', 'Tolak-menolak', 'Diam, tidak terjadi apa-apa', 'Kedua magnet kehilangan kemagnetannya'];

        foreach ([[['S', 'U'], ['U', 'S']], [['U', 'S'], ['U', 'S']], [['U', 'S'], ['S', 'U']], [['S', 'U'], ['S', 'U']]] as [$kiri, $kanan]) {
            // Only the two facing poles matter: same pole repels, different attracts.
            $benar = $kiri[1] === $kanan[0] ? 'Tolak-menolak' : 'Tarik-menarik';

            $this->buat(
                'Dua buah magnet batang didekatkan seperti pada gambar berikut.',
                IpaSdGambar::duaMagnet($kiri, $kanan),
                'Dua magnet batang didekatkan',
                'Yang terjadi pada kedua magnet tersebut adalah ....',
                $benar,
                array_values(array_diff($akibat, [$benar])),
            );
        }

        $benda = ['Paku besi', 'Penghapus', 'Pensil kayu', 'Kertas'];

        $this->buat(
            'Perhatikan magnet dan benda-benda berikut!',
            IpaSdGambar::magnetDanBenda($benda),
            'Magnet batang dan empat benda',
            'Benda yang dapat ditarik oleh magnet adalah ....',
            'Paku besi',
            ['Penghapus', 'Pensil kayu', 'Kertas'],
        );
    }

    private function perubahanWujud(): void
    {
        $nama = [
            'Padat>Cair' => 'Mencair', 'Cair>Padat' => 'Membeku', 'Cair>Gas' => 'Menguap',
            'Gas>Cair' => 'Mengembun', 'Padat>Gas' => 'Menyublim', 'Gas>Padat' => 'Mengkristal',
        ];

        foreach (['Padat>Cair', 'Cair>Padat', 'Cair>Gas', 'Gas>Cair', 'Padat>Gas'] as $i => $kunci) {
            $benar = $nama[$kunci];
            $lain = array_values(array_diff($nama, [$benar]));

            $this->buat(
                'Perhatikan diagram perubahan wujud zat berikut!',
                IpaSdGambar::perubahanWujud(explode('>', $kunci)),
                'Diagram perubahan wujud zat antara padat, cair, dan gas',
                'Perubahan wujud yang ditunjukkan oleh anak panah berwarna merah disebut ....',
                $benar,
                // Rotate which three wrong names appear, so options vary per soal.
                [$lain[$i % 5], $lain[($i + 1) % 5], $lain[($i + 2) % 5]],
            );
        }
    }

    private function kecepatan(): void
    {
        foreach ([[120, 2, 'km', 'jam'], [180, 20, 'm', 'detik'], [150, 3, 'km', 'jam'], [240, 40, 'm', 'detik'], [280, 4, 'km', 'jam']] as [$jarak, $waktu, $satuanJarak, $satuanWaktu]) {
            $v = intdiv($jarak, $waktu);
            $satuan = "{$satuanJarak}/{$satuanWaktu}";

            $this->buat(
                'Sebuah mobil bergerak dari garis start ke garis finish seperti pada gambar berikut.',
                IpaSdGambar::gerakMobil("{$jarak} {$satuanJarak}", "{$waktu} {$satuanWaktu}"),
                "Mobil menempuh {$jarak} {$satuanJarak} dalam {$waktu} {$satuanWaktu}",
                'Kecepatan rata-rata mobil tersebut adalah ....',
                "{$v} {$satuan}",
                [($v * 2)." {$satuan}", intdiv($v, 2)." {$satuan}", ($v + 10)." {$satuan}"],
            );
        }
    }

    /**
     * Writes the gambar, then the soal with the correct option rotated
     * through A–D by soal number so the answer key isn't all one letter.
     *
     * @param  list<string>  $salah  Exactly three wrong options.
     */
    private function buat(string $pengantar, string $svg, string $alt, string $pertanyaan, string $benar, array $salah): void
    {
        $this->nomor++;
        $opsi = [...$salah];

        if (count($opsi) !== 3 || count(array_unique([$benar, ...$opsi])) !== 4) {
            throw new RuntimeException("Soal #{$this->nomor}: opsi harus 1 benar + 3 salah yang berbeda.");
        }

        $posisi = ($this->nomor - 1) % 4;
        array_splice($opsi, $posisi, 0, [$benar]);

        $path = "soal/{$this->bankSoal->id}/".self::PREFIX_GAMBAR.str_pad((string) $this->nomor, 2, '0', STR_PAD_LEFT).'.svg';
        Storage::disk('public')->put($path, $svg);
        // Root-relative, like the editor saves (see resources/js/soal-editor.js).
        $src = parse_url(Storage::disk('public')->url($path), PHP_URL_PATH);

        Soal::create([
            'bank_soal_id' => $this->bankSoal->id,
            'pertanyaan' => SoalContentPurifier::bersihkan(
                '<p>'.e($pengantar).'</p><p><img src="'.e($src).'" alt="'.e($alt).'" /></p><p>'.e($pertanyaan).'</p>'
            ),
            'pilih_a' => SoalContentPurifier::bersihkan('<p>'.e($opsi[0]).'</p>'),
            'pilih_b' => SoalContentPurifier::bersihkan('<p>'.e($opsi[1]).'</p>'),
            'pilih_c' => SoalContentPurifier::bersihkan('<p>'.e($opsi[2]).'</p>'),
            'pilih_d' => SoalContentPurifier::bersihkan('<p>'.e($opsi[3]).'</p>'),
            'pilih_e' => null,
            'jawaban' => ['A', 'B', 'C', 'D'][$posisi],
        ]);
    }

    /**
     * Previously seeded soal still in use by a peserta's attempt
     * (peserta_soal restricts deletes) are kept rather than failing the run.
     */
    private function hapusSeedLama(): void
    {
        $lama = Soal::query()
            ->where('bank_soal_id', $this->bankSoal->id)
            ->where('pertanyaan', 'like', '%'.self::PREFIX_GAMBAR.'%')
            ->whereNotIn('id', DB::table('peserta_soal')->select('soal_id'));

        $dipakai = Soal::query()
            ->where('bank_soal_id', $this->bankSoal->id)
            ->where('pertanyaan', 'like', '%'.self::PREFIX_GAMBAR.'%')
            ->count() - (clone $lama)->count();

        if ($dipakai > 0) {
            $this->command?->warn("{$dipakai} soal seed lama sudah dipakai di ujian, jadi tidak dihapus.");
        }

        $lama->delete();
    }
}
