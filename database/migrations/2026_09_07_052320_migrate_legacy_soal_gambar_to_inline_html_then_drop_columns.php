<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Retires the per-slot gambar dropzone system (see the earlier
 * 2026_09_05_133842_add_gambar_columns_to_soal_table migration) now that
 * TinyMCE (resources/js/soal-editor.js) lets an admin embed an image
 * directly inline in a soal's pertanyaan/pilihan HTML instead. Any soal
 * still carrying an image in one of the old `*_gambar` columns gets that
 * image appended as an inline <img> in the matching text column before the
 * columns are dropped, so nothing existing goes missing.
 *
 * Deliberately NOT touched here: legacy plain-text content with no gambar
 * at all is left exactly as it was (still plain text, not HTML). Almost all
 * of it renders identically either way, but text containing a literal `<`,
 * `>`, or unescaped `&` (e.g. a soal reading "jika a < b") would need
 * escaping to render safely once the CBT exam page switches from
 * `x-text` to `x-html` — auto-detecting "is this already-HTML from the new
 * editor or legacy plain text" heavily enough to do that safely (without
 * double-escaping real HTML) isn't attempted here; it's a known gap.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const KOLOM_GAMBAR_KE_TEKS = [
        'pertanyaan_gambar' => 'pertanyaan',
        'pilih_a_gambar' => 'pilih_a',
        'pilih_b_gambar' => 'pilih_b',
        'pilih_c_gambar' => 'pilih_c',
        'pilih_d_gambar' => 'pilih_d',
        'pilih_e_gambar' => 'pilih_e',
    ];

    public function up(): void
    {
        $this->pindahkanGambarKeKontenHtml();

        Schema::table('soal', function (Blueprint $table): void {
            $table->dropColumn(array_keys(self::KOLOM_GAMBAR_KE_TEKS));
        });
    }

    public function down(): void
    {
        Schema::table('soal', function (Blueprint $table): void {
            foreach (array_keys(self::KOLOM_GAMBAR_KE_TEKS) as $kolom) {
                $table->string($kolom)->nullable();
            }
        });

        // Not reversed: once an image is folded into the text column as an
        // inline <img>, there's no reliable way to tell it apart from any
        // other inline image the admin added — rows just keep the image
        // inline (rather than losing it) after a rollback.
    }

    private function pindahkanGambarKeKontenHtml(): void
    {
        foreach (self::KOLOM_GAMBAR_KE_TEKS as $kolomGambar => $kolomTeks) {
            DB::table('soal')
                ->whereNotNull($kolomGambar)
                ->get(['id', $kolomGambar, $kolomTeks])
                ->each(function (object $soal) use ($kolomGambar, $kolomTeks): void {
                    $url = Storage::disk('public')->url($soal->{$kolomGambar});
                    $imgTag = '<img src="'.e($url).'">';
                    $teksLama = trim((string) ($soal->{$kolomTeks} ?? ''));

                    DB::table('soal')->where('id', $soal->id)->update([
                        $kolomTeks => trim($teksLama.' '.$imgTag),
                    ]);
                });
        }
    }
};
