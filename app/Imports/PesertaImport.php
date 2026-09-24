<?php

namespace App\Imports;

use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use App\Models\PesertaUjian;
use App\Models\Ujian;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk peserta import. Expects columns: noreg (NISN, 10 digit), nama, jenjang
 * (Jenjang.nama — e.g. "SLTA"), asal_sekolah, password (optional: blank
 * defaults to noreg, or literally "acak" to generate a random one), and
 * three competition flags — osains/omtk/obing (0/1) — that record the
 * peserta's interest in that mapel ("minat lomba", see
 * App\Models\Peserta::pelajaranLomba()) and, if a matching Ujian already
 * exists for their jenjang, register them into it right away too.
 *
 * Processes row-by-row so one bad row doesn't abort the whole file; failures
 * are collected in $errors and surfaced back to the admin. $notes carries
 * non-blocking information about a row that still imported fine (e.g. no
 * matching ujian yet for a flagged mapel).
 */
class PesertaImport implements ToCollection, WithHeadingRow
{
    /**
     * Which competition-flag column maps to which Pelajaran.nama.
     *
     * @var array<string, string>
     */
    private const MAPEL_LOMBA = [
        'osains' => 'Sains',
        'omtk' => 'Matematika',
        'obing' => 'Bahasa Inggris',
    ];

    public int $imported = 0;

    /** @var array<int, string> */
    public array $errors = [];

    /** @var array<int, string> */
    public array $notes = [];

    /**
     * noreg => plaintext password, for rows where a random one was
     * generated — the admin has no other way to learn what it was.
     *
     * @var array<string, string>
     */
    public array $generatedPasswords = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $baris = $index + 2; // +1 for zero-index, +1 for the heading row

            $data = [
                'noreg' => trim((string) ($row['noreg'] ?? '')),
                'nama' => trim((string) ($row['nama'] ?? '')),
                'jenjang' => trim((string) ($row['jenjang'] ?? '')),
                'asal_sekolah' => trim((string) ($row['asal_sekolah'] ?? '')),
                'password' => trim((string) ($row['password'] ?? '')),
            ];

            $validator = Validator::make($data, [
                // noreg is a peserta's NISN — also their login id (App\Models\Peserta).
                'noreg' => ['required', 'string', 'size:10', 'unique:peserta,noreg'],
                'nama' => ['required', 'string', 'max:255'],
                'jenjang' => ['required', 'string'],
                'asal_sekolah' => ['required', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Baris {$baris}: ".$validator->errors()->first();

                continue;
            }

            $jenjang = Jenjang::query()->where('nama', $data['jenjang'])->first();

            if (! $jenjang) {
                $this->errors[] = "Baris {$baris}: jenjang '{$data['jenjang']}' tidak ditemukan.";

                continue;
            }

            $passwordPlain = match (true) {
                strcasecmp($data['password'], 'acak') === 0 => Str::password(10, symbols: false),
                $data['password'] !== '' => $data['password'],
                default => $data['noreg'],
            };

            $peserta = Peserta::create([
                'jenjang_id' => $jenjang->id,
                'noreg' => $data['noreg'],
                'nama' => $data['nama'],
                'asal_sekolah' => $data['asal_sekolah'],
                'password' => Hash::make($passwordPlain),
                'password_plain' => $passwordPlain,
            ]);

            if (strcasecmp($data['password'], 'acak') === 0) {
                $this->generatedPasswords[$peserta->noreg] = $passwordPlain;
            }

            $this->daftarkanLomba($peserta, $jenjang, $row, $baris);

            $this->imported++;
        }
    }

    /**
     * Records $peserta's interest in every mapel flagged "1" ("minat lomba"
     * — see Peserta::pelajaranLomba()), then also registers them (a
     * PesertaUjian row each) into every already-existing Ujian matching
     * their jenjang for that mapel. Never blocks the import: a mapel with
     * no matching Ujian yet still gets the interest recorded, just with a
     * $notes entry — daftarkan later via "Daftarkan Peserta yang Berminat"
     * on that Ujian's Peserta Ujian admin page once it's created.
     *
     * @param  array<string, mixed>|Collection<string, mixed>  $row  A real import
     *                                                               row is a Collection (Maatwebsite's WithHeadingRow); tests exercising
     *                                                               collection() directly pass plain arrays instead — both support the
     *                                                               array-offset access this method needs.
     */
    private function daftarkanLomba(Peserta $peserta, Jenjang $jenjang, array|Collection $row, int $baris): void
    {
        foreach (self::MAPEL_LOMBA as $kolom => $namaPelajaran) {
            if (trim((string) ($row[$kolom] ?? '')) !== '1') {
                continue;
            }

            $pelajaran = Pelajaran::query()->where('nama', $namaPelajaran)->first();

            if (! $pelajaran) {
                $this->notes[] = "Baris {$baris}: {$peserta->nama} ditandai ikut lomba {$namaPelajaran}, tapi mapel '{$namaPelajaran}' tidak ditemukan di Master Data.";

                continue;
            }

            $peserta->pelajaranLomba()->attach($pelajaran->id);

            $ujianCocok = Ujian::query()->where('jenjang_id', $jenjang->id)->where('pelajaran_id', $pelajaran->id)->get();

            if ($ujianCocok->isEmpty()) {
                $this->notes[] = "Baris {$baris}: {$peserta->nama} dicatat berminat lomba {$namaPelajaran}, tapi belum ada ujian {$namaPelajaran} untuk jenjang {$jenjang->nama} — daftarkan lewat \"Daftarkan Peserta yang Berminat\" begitu ujiannya dibuat.";

                continue;
            }

            foreach ($ujianCocok as $ujian) {
                PesertaUjian::create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);
            }
        }
    }
}
