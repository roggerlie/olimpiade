<?php

namespace App\Exports;

use PhpOffice\PhpWord\PhpWord;

/**
 * Builds the downloadable Word template for soal-with-gambar import — a
 * single table (Pertanyaan | A | B | C | D | E | Jawaban) admins fill in
 * directly in Word/LibreOffice, one row per soal. Any of the Pertanyaan/A-E
 * cells may hold text, an inserted picture, or both; App\Imports\SoalWordImport
 * reads this exact table shape back — keep both in sync.
 *
 * Not a Maatwebsite\Excel export (this produces a .docx, not a spreadsheet),
 * so it doesn't implement any of that package's interfaces — just a plain
 * class the `bank-soal.soal.template-word` route calls directly.
 */
class SoalWordTemplateExport
{
    private const KOLOM = ['Pertanyaan', 'A', 'B', 'C', 'D', 'E', 'Jawaban'];

    public function build(): PhpWord
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        $section->addText('Template Soal (Word)', ['bold' => true, 'size' => 14]);
        $section->addTextBreak();

        $section->addText('Cara mengisi:', ['bold' => true]);
        $section->addListItem('Jangan mengubah baris judul (header) tabel di bawah.');
        $section->addListItem('Satu baris tabel = satu soal. Tambah baris baru untuk soal berikutnya (klik di sel terakhir lalu tekan Tab).');
        $section->addListItem('Kolom Pertanyaan dan A-E boleh diisi teks, gambar, atau dua-duanya — taruh kursor di dalam sel, lalu Insert > Pictures untuk menyisipkan gambar ke sel itu.');
        $section->addListItem('Pertanyaan wajib punya teks (gambar cuma pelengkap). Pilihan A-D wajib diisi teks atau gambar, minimal salah satu. Kolom E boleh dikosongkan total kalau soal cuma butuh 4 pilihan.');
        $section->addListItem('Kolom Jawaban diisi salah satu huruf saja: A, B, C, D, atau E — sesuai pilihan mana yang benar.');
        $section->addTextBreak();

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);

        $table->addRow();
        foreach (self::KOLOM as $judul) {
            $table->addCell(1500)->addText($judul, ['bold' => true]);
        }

        $contoh = ['Berapa hasil dari 12 + 8?', '18', '20', '22', '24', '', 'B'];
        $table->addRow();
        foreach ($contoh as $isi) {
            $table->addCell(1500)->addText($isi);
        }

        return $phpWord;
    }
}
