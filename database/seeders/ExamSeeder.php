<?php

namespace Database\Seeders;

use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Siswa;
use App\Models\SiswaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Database\Seeder;

/**
 * Seeds a complete, ready-to-take exam per jenjang (SD/SMP/SMA): bank soal
 * with real arithmetic questions, an ujian whose session window is open
 * right now, and a few siswa already registered for it — so a student can
 * log in and run the whole "mulai → kerjakan → submit" flow without any
 * admin setup first.
 */
class ExamSeeder extends Seeder
{
    /**
     * Login credentials handed back to the console after seeding, so
     * whoever ran `db:seed` doesn't have to go digging in the database.
     *
     * @var array<int, array{noreg: string, nama: string, jenjang: string}>
     */
    private array $siswaDibuat = [];

    public function run(): void
    {
        $pelajaran = Pelajaran::factory()->create(['nama' => 'Matematika']);

        $noreg = 1000001;

        foreach ($this->jenjangUjian() as $tingkat => $config) {
            $jenjang = Jenjang::factory()->create([
                'kode' => $config['kode'],
                'nama' => $tingkat,
            ]);

            $bankSoal = BankSoal::factory()->create([
                'jenjang_id' => $jenjang->id,
                'pelajaran_id' => $pelajaran->id,
                'nama' => "Bank Soal Matematika {$tingkat}",
                'deskripsi' => "Soal aritmatika tingkat {$tingkat} untuk uji coba CBT.",
            ]);

            // Bank punya lebih banyak soal daripada yang dipakai per ujian,
            // supaya ExamAttemptService punya sesuatu untuk diacak.
            $this->buatSoal($bankSoal->id, $tingkat, jumlah: 20);

            $ujian = Ujian::factory()->create([
                'bank_soal_id' => $bankSoal->id,
                'jenjang_id' => $jenjang->id,
                'pelajaran_id' => $pelajaran->id,
                'nama' => "Ujian Matematika {$tingkat}",
                'jumlah_soal' => 10,
                'durasi_detik' => 15 * 60,
                'sesi_mulai' => now()->subMinutes(10),
                'sesi_selesai' => now()->addHours(6),
                'deskripsi' => 'Sesi ujian percobaan — dibuat oleh ExamSeeder.',
            ]);

            // 3 siswa per jenjang, sudah terdaftar di ujian tapi belum
            // "Mulai" — siap dipakai untuk test end-to-end dari login.
            for ($i = 1; $i <= 3; $i++) {
                $siswa = Siswa::factory()->create([
                    'jenjang_id' => $jenjang->id,
                    'noreg' => str_pad((string) $noreg++, 7, '0', STR_PAD_LEFT),
                    'nama' => "Siswa Demo {$tingkat} {$i}",
                    'asal_sekolah' => "Sekolah Demo {$tingkat}",
                ]);

                SiswaUjian::factory()->create([
                    'siswa_id' => $siswa->id,
                    'ujian_id' => $ujian->id,
                ]);

                $this->siswaDibuat[] = [
                    'noreg' => $siswa->noreg,
                    'nama' => $siswa->nama,
                    'jenjang' => $tingkat,
                ];
            }
        }

        $this->tampilkanKredensial();
    }

    /**
     * @return array<string, array{kode: string}>
     */
    private function jenjangUjian(): array
    {
        return [
            'SD' => ['kode' => 'SD'],
            'SMP' => ['kode' => 'SP'],
            'SMA' => ['kode' => 'SA'],
        ];
    }

    private function buatSoal(int $bankSoalId, string $tingkat, int $jumlah): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            [$pertanyaan, $jawabanBenar] = $this->buatPertanyaan($tingkat);

            $opsi = collect([$jawabanBenar]);

            while ($opsi->count() < 4) {
                $kandidat = max(0, $jawabanBenar + random_int(-10, 10));

                if (! $opsi->contains($kandidat)) {
                    $opsi->push($kandidat);
                }
            }

            $opsi = $opsi->shuffle()->values();
            $huruf = ['A', 'B', 'C', 'D'][$opsi->search($jawabanBenar)];

            Soal::factory()->create([
                'bank_soal_id' => $bankSoalId,
                'pertanyaan' => $pertanyaan,
                'pilih_a' => (string) $opsi[0],
                'pilih_b' => (string) $opsi[1],
                'pilih_c' => (string) $opsi[2],
                'pilih_d' => (string) $opsi[3],
                'pilih_e' => null,
                'jawaban' => $huruf,
            ]);
        }
    }

    /**
     * Generates one real, answerable arithmetic question sized to the
     * jenjang's expected difficulty.
     *
     * @return array{0: string, 1: int}
     */
    private function buatPertanyaan(string $tingkat): array
    {
        return match ($tingkat) {
            'SD' => (function () {
                $a = random_int(1, 50);
                $b = random_int(1, 50);

                return ["Berapa hasil dari {$a} + {$b}?", $a + $b];
            })(),
            'SMP' => (function () {
                $a = random_int(2, 12);
                $b = random_int(2, 12);

                return ["Berapa hasil dari {$a} × {$b}?", $a * $b];
            })(),
            default => (function () {
                $x = random_int(2, 9);
                $a = random_int(2, 9);
                $b = random_int(1, 20);

                return ["Jika x = {$x}, berapa nilai dari {$a}x + {$b}?", $a * $x + $b];
            })(),
        };
    }

    private function tampilkanKredensial(): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->info('Data ujian percobaan berhasil dibuat. Login siswa (password: "password"):');

        $this->command->table(
            ['Noreg (username)', 'Nama', 'Jenjang'],
            $this->siswaDibuat
        );
    }
}
