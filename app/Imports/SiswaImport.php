<?php

namespace App\Imports;

use App\Models\Jenjang;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk peserta import. Expects columns: noreg, nama, jenjang (Jenjang.kode),
 * asal_sekolah, password (optional — defaults to noreg when left blank).
 *
 * Processes row-by-row so one bad row doesn't abort the whole file; failures
 * are collected in $errors and surfaced back to the admin.
 */
class SiswaImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    /** @var array<int, string> */
    public array $errors = [];

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
                'noreg' => ['required', 'string', 'size:7', 'unique:siswa,noreg', 'unique:users,username'],
                'nama' => ['required', 'string', 'max:255'],
                'jenjang' => ['required', 'string'],
                'asal_sekolah' => ['required', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Baris {$baris}: ".$validator->errors()->first();

                continue;
            }

            $jenjang = Jenjang::query()->where('kode', $data['jenjang'])->first();

            if (! $jenjang) {
                $this->errors[] = "Baris {$baris}: kode jenjang '{$data['jenjang']}' tidak ditemukan.";

                continue;
            }

            DB::transaction(function () use ($data, $jenjang): void {
                $user = User::create([
                    'username' => $data['noreg'],
                    'name' => $data['nama'],
                    'password' => Hash::make($data['password'] !== '' ? $data['password'] : $data['noreg']),
                ]);
                $user->assignRole('siswa');

                Siswa::create([
                    'user_id' => $user->id,
                    'jenjang_id' => $jenjang->id,
                    'noreg' => $data['noreg'],
                    'nama' => $data['nama'],
                    'asal_sekolah' => $data['asal_sekolah'],
                ]);
            });

            $this->imported++;
        }
    }
}
