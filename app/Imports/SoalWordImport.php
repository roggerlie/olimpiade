<?php

namespace App\Imports;

use App\Models\Soal;
use App\Support\SoalContentPurifier;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Bulk soal import from the Word template (see SoalWordTemplateExport) — the
 * one import path that carries gambar, since Excel cells can't hold an image
 * the way a Word table cell naturally can.
 *
 * Deliberately does NOT use PhpWord's reader (IOFactory::load) to walk the
 * table: PhpWord's own writer is what generates the template, but reading it
 * back — especially after an admin has edited it by hand in real Word or
 * LibreOffice and pasted images into cells — is far more predictable done by
 * reading the .docx's underlying XML directly. A .docx is a zip archive;
 * `word/document.xml` holds the table's text as plain <w:t> runs, and each
 * inserted picture is a <w:drawing>...<a:blip r:embed="rIdN"/> pointing at an
 * entry in `word/_rels/document.xml.rels`, which resolves to the actual image
 * file under `word/media/`. Walking that directly means exactly one, fully
 * controlled code path for "does this cell have text, an image, or both" —
 * no dependency on how faithfully a third-party reader reconstructs a
 * document it didn't write itself.
 *
 * Understands both image formats a .docx can carry in a table cell: modern
 * DrawingML (<w:drawing>...<a:blip r:embed="rIdN"/>), what current Word's and
 * LibreOffice's own "Insert > Picture" produce, and the older VML fallback
 * (<w:pict><v:shape><v:imagedata r:id="rIdN"/>) — which, somewhat
 * surprisingly, is what PhpWord's own writer emits for an image placed
 * inside a table cell (confirmed by inspecting its output directly), so
 * supporting it is also what makes this importer's own test suite exercise
 * a real file rather than a hand-faked one.
 */
class SoalWordImport
{
    private const KOLOM = ['pertanyaan', 'A', 'B', 'C', 'D', 'E', 'jawaban'];

    private const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const NS_R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const NS_A = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const NS_V = 'urn:schemas-microsoft-com:vml';

    public int $imported = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function __construct(private readonly int $bankSoalId) {}

    public function import(string $path): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            $this->errors[] = 'File Word tidak bisa dibuka — pastikan formatnya .docx yang valid dan tidak rusak.';

            return;
        }

        $documentXml = $zip->getFromName('word/document.xml');

        if ($documentXml === false) {
            $this->errors[] = 'File Word tidak punya isi dokumen yang bisa dibaca.';
            $zip->close();

            return;
        }

        $relasi = $this->bacaRelasi($zip->getFromName('word/_rels/document.xml.rels'));

        $dom = new DOMDocument;
        $dom->loadXML($documentXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS_W);
        $xpath->registerNamespace('a', self::NS_A);
        $xpath->registerNamespace('v', self::NS_V);

        $tabel = $xpath->query('//w:tbl');

        if ($tabel === false || $tabel->length === 0) {
            $this->errors[] = 'Tidak ditemukan tabel di dalam file Word. Gunakan template yang disediakan, jangan bikin dokumen baru dari nol.';
            $zip->close();

            return;
        }

        $baris = $xpath->query('.//w:tr', $tabel->item(0));

        // Row 0 is the header (Pertanyaan | A | B | C | D | E | Jawaban) — skip it.
        for ($i = 1; $i < $baris->length; $i++) {
            $nomorBaris = $i + 1; // human-facing: header counts as row 1, matching what's visible in the table itself.
            $this->prosesBaris($xpath, $baris->item($i), $relasi, $zip, $nomorBaris);
        }

        $zip->close();
    }

    private function prosesBaris(DOMXPath $xpath, DOMElement $tr, array $relasi, ZipArchive $zip, int $nomorBaris): void
    {
        $sel = $xpath->query('.//w:tc', $tr);

        if ($sel->length < 7) {
            $this->errors[] = "Baris {$nomorBaris}: jumlah kolom kurang dari 7 (Pertanyaan, A, B, C, D, E, Jawaban).";

            return;
        }

        $kolom = [];
        foreach (self::KOLOM as $index => $kunci) {
            /** @var DOMElement $cell */
            $cell = $sel->item($index);
            $kolom[$kunci] = [
                'teks' => trim($this->bacaTeks($xpath, $cell)),
                'gambar' => $kunci === 'jawaban' ? null : $this->bacaGambar($xpath, $cell, $relasi, $zip),
            ];
        }

        $pertanyaan = $kolom['pertanyaan']['teks'];
        $jawaban = strtoupper($kolom['jawaban']['teks']);

        if ($pertanyaan === '') {
            $this->errors[] = "Baris {$nomorBaris}: kolom Pertanyaan harus diisi teks (gambar saja tidak cukup).";

            return;
        }

        if (! in_array($jawaban, ['A', 'B', 'C', 'D', 'E'], true)) {
            $this->errors[] = "Baris {$nomorBaris}: kolom Jawaban harus salah satu dari A, B, C, D, atau E.";

            return;
        }

        foreach (['A', 'B', 'C', 'D'] as $huruf) {
            if ($kolom[$huruf]['teks'] === '' && $kolom[$huruf]['gambar'] === null) {
                $this->errors[] = "Baris {$nomorBaris}: pilihan {$huruf} harus diisi teks atau gambar.";

                return;
            }
        }

        if ($jawaban === 'E' && $kolom['E']['teks'] === '' && $kolom['E']['gambar'] === null) {
            $this->errors[] = "Baris {$nomorBaris}: Jawaban E dipilih tapi pilihan E kosong (teks maupun gambar).";

            return;
        }

        Soal::create([
            'bank_soal_id' => $this->bankSoalId,
            'pertanyaan' => $this->bangunHtml($kolom['pertanyaan']['teks'], $kolom['pertanyaan']['gambar']),
            'pilih_a' => $this->bangunHtml($kolom['A']['teks'], $kolom['A']['gambar']),
            'pilih_b' => $this->bangunHtml($kolom['B']['teks'], $kolom['B']['gambar']),
            'pilih_c' => $this->bangunHtml($kolom['C']['teks'], $kolom['C']['gambar']),
            'pilih_d' => $this->bangunHtml($kolom['D']['teks'], $kolom['D']['gambar']),
            'pilih_e' => $this->bangunHtml($kolom['E']['teks'], $kolom['E']['gambar']),
            'jawaban' => $jawaban,
        ]);

        $this->imported++;
    }

    /**
     * Combines a cell's plain text (escaped — Word text isn't HTML, unlike
     * what the manual TinyMCE form now produces) and its extracted image
     * (as an inline <img>, matching where the manual form's own uploads
     * land — see resources/js/soal-editor.js) into one HTML value, then runs
     * it through the same purifier the manual form uses so both paths save
     * content in an identical shape. Null (not '') when the cell was
     * entirely empty, so it still saves as NULL for the optional pilihan E.
     */
    private function bangunHtml(string $teks, ?string $gambarPath): ?string
    {
        if ($teks === '' && $gambarPath === null) {
            return null;
        }

        $bagian = [];

        if ($teks !== '') {
            $bagian[] = e($teks);
        }

        if ($gambarPath !== null) {
            $bagian[] = '<img src="'.e(Storage::disk('public')->url($gambarPath)).'">';
        }

        return SoalContentPurifier::bersihkan(implode(' ', $bagian));
    }

    private function bacaTeks(DOMXPath $xpath, DOMElement $cell): string
    {
        $teks = [];

        foreach ($xpath->query('.//w:t', $cell) as $node) {
            $teks[] = $node->textContent;
        }

        return implode(' ', $teks);
    }

    /**
     * The relationship id pointing at a cell's embedded picture, whichever
     * of the two shapes it was authored in — DrawingML first (the common,
     * modern case), VML as a fallback. Null if the cell has no image at all.
     */
    private function cariRelationshipIdGambar(DOMXPath $xpath, DOMElement $cell): ?string
    {
        $blip = $xpath->query('.//a:blip', $cell);

        if ($blip->length > 0) {
            return $blip->item(0)->getAttributeNS(self::NS_R, 'embed') ?: null;
        }

        $imagedata = $xpath->query('.//v:imagedata', $cell);

        if ($imagedata->length > 0) {
            return $imagedata->item(0)->getAttributeNS(self::NS_R, 'id') ?: null;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $relasi
     */
    private function bacaGambar(DOMXPath $xpath, DOMElement $cell, array $relasi, ZipArchive $zip): ?string
    {
        $rId = $this->cariRelationshipIdGambar($xpath, $cell);

        if ($rId === null || ! isset($relasi[$rId])) {
            return null;
        }

        // Targets in the rels file are relative to word/ (e.g. "media/image1.png").
        $target = 'word/'.ltrim($relasi[$rId], '/');
        $binary = $zip->getFromName($target);

        if ($binary === false) {
            return null;
        }

        $ekstensi = strtolower(pathinfo($target, PATHINFO_EXTENSION)) ?: 'png';
        $path = "soal/{$this->bankSoalId}/".Str::random(40).".{$ekstensi}";
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * @return array<string, string> rId => target path
     */
    private function bacaRelasi(string|false $relsXml): array
    {
        if ($relsXml === false) {
            return [];
        }

        $dom = new DOMDocument;
        $dom->loadXML($relsXml);

        $map = [];

        foreach ($dom->getElementsByTagName('Relationship') as $rel) {
            $map[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
        }

        return $map;
    }
}
