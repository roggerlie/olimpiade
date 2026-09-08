<?php

namespace App\Imports;

use App\Models\Soal;
use App\Support\SoalContentPurifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk soal import, scoped to a single bank soal (the one whose "Daftar
 * Soal" page the import was opened from — no jenjang/pelajaran/bank-soal
 * column needed, unlike PesertaImport which is global). Expects columns:
 * pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e
 * (optional), jawaban. Text only — this format doesn't carry images; use the
 * Word import for soal with gambar.
 *
 * Every field is HTML-escaped before saving even though it's plain text
 * from a spreadsheet cell: the CBT exam page renders soal content as HTML
 * (x-html, matching the rich text the manual TinyMCE form and Word import
 * both produce — see App\Support\SoalContentPurifier), so a cell containing
 * a literal `<`, `>`, or `&` (e.g. "jika a < b") would otherwise be
 * misinterpreted as markup instead of displayed as-is.
 *
 * Processes row-by-row so one bad row doesn't abort the whole file; failures
 * are collected in $errors and surfaced back to the admin.
 */
class SoalImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function __construct(private readonly int $bankSoalId) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $baris = $index + 2; // +1 for zero-index, +1 for the heading row

            $data = [
                'pertanyaan' => trim((string) ($row['pertanyaan'] ?? '')),
                'pilihan_a' => trim((string) ($row['pilihan_a'] ?? '')),
                'pilihan_b' => trim((string) ($row['pilihan_b'] ?? '')),
                'pilihan_c' => trim((string) ($row['pilihan_c'] ?? '')),
                'pilihan_d' => trim((string) ($row['pilihan_d'] ?? '')),
                'pilihan_e' => trim((string) ($row['pilihan_e'] ?? '')),
                'jawaban' => strtoupper(trim((string) ($row['jawaban'] ?? ''))),
            ];

            $validator = Validator::make($data, [
                'pertanyaan' => ['required', 'string'],
                'pilihan_a' => ['required', 'string'],
                'pilihan_b' => ['required', 'string'],
                'pilihan_c' => ['required', 'string'],
                'pilihan_d' => ['required', 'string'],
                'pilihan_e' => ['nullable', 'string'],
                'jawaban' => ['required', 'in:A,B,C,D,E'],
            ])->after(function ($validator) use ($data) {
                if ($data['jawaban'] === 'E' && $data['pilihan_e'] === '') {
                    $validator->errors()->add('jawaban', 'Jawaban E dipilih tapi pilihan E kosong.');
                }
            });

            if ($validator->fails()) {
                $this->errors[] = "Baris {$baris}: ".$validator->errors()->first();

                continue;
            }

            Soal::create([
                'bank_soal_id' => $this->bankSoalId,
                'pertanyaan' => SoalContentPurifier::bersihkan(e($data['pertanyaan'])),
                'pilih_a' => SoalContentPurifier::bersihkan(e($data['pilihan_a'])),
                'pilih_b' => SoalContentPurifier::bersihkan(e($data['pilihan_b'])),
                'pilih_c' => SoalContentPurifier::bersihkan(e($data['pilihan_c'])),
                'pilih_d' => SoalContentPurifier::bersihkan(e($data['pilihan_d'])),
                'pilih_e' => $data['pilihan_e'] !== '' ? SoalContentPurifier::bersihkan(e($data['pilihan_e'])) : null,
                'jawaban' => $data['jawaban'],
            ]);

            $this->imported++;
        }
    }
}
