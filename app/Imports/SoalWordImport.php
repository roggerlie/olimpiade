<?php

namespace App\Imports;

use App\Models\Soal;
use App\Support\OmmlKeMathml;
use App\Support\SoalContentPurifier;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Bulk soal import from the Word template (see SoalWordTemplateExport) — the
 * one import path that carries gambar and rumus, since Excel cells can't hold
 * either the way a Word table cell naturally can.
 *
 * Deliberately does NOT use PhpWord's reader (IOFactory::load) to walk the
 * table: PhpWord's own writer is what generates the template, but reading it
 * back — especially after an admin has edited it by hand in real Word or
 * LibreOffice and pasted images/equations into cells — is far more
 * predictable done by reading the .docx's underlying XML directly. A .docx
 * is a zip archive; `word/document.xml` holds the table, and each inserted
 * picture points (via an r:embed / r:id relationship id) at an entry in
 * `word/_rels/document.xml.rels`, which resolves to the actual image file
 * under `word/media/`.
 *
 * Each cell is read in document order, run by run, so its content keeps its
 * shape:
 *  - text, with Word's superscript/subscript formatting kept as <sup>/<sub>
 *    (s² stays s², not "s 2");
 *  - Word's own equations (Insert → Equation, OMML <m:oMath>) converted to
 *    MathML by App\Support\OmmlKeMathml — browsers render <math> natively;
 *  - pictures, inline where they sit, in both shapes a .docx can carry:
 *    DrawingML (<w:drawing>…<a:blip r:embed/>, what current Word/LibreOffice
 *    produce) and VML (<w:pict><v:imagedata r:id/>, which is what PhpWord's
 *    own writer emits for an image inside a table cell);
 *  - MathType equations (OLE objects) are rejected with a clear message:
 *    all the .docx carries for them is a WMF/EMF preview browsers can't
 *    display, and saving that would just be a broken image.
 *
 * Anything that can't be carried over faithfully (an unrecognised part of
 * an equation, a non-equation embedded object) is reported in $peringatan,
 * so it never disappears silently.
 */
class SoalWordImport
{
    private const KOLOM = ['pertanyaan', 'A', 'B', 'C', 'D', 'E', 'jawaban'];

    private const NAMA_KOLOM = ['pertanyaan' => 'Pertanyaan', 'A' => 'pilihan A', 'B' => 'pilihan B', 'C' => 'pilihan C', 'D' => 'pilihan D', 'E' => 'pilihan E'];

    private const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const NS_R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const NS_A = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const NS_V = 'urn:schemas-microsoft-com:vml';

    private const NS_O = 'urn:schemas-microsoft-com:office:office';

    private const NS_MC = 'http://schemas.openxmlformats.org/markup-compatibility/2006';

    /** Browsers can't display these, so a picture in one of them is rejected rather than saved broken. */
    private const EKSTENSI_TIDAK_TAMPIL = ['wmf', 'emf', 'tif', 'tiff'];

    public int $imported = 0;

    /** @var array<int, string> Rows that were skipped, and why. */
    public array $errors = [];

    /** @var array<int, string> Rows that were imported, but with something the admin should double-check. */
    public array $peringatan = [];

    /** @var array<string, string> rId => target path, for the document being imported. */
    private array $relasi = [];

    private ?ZipArchive $zip = null;

    /** @var array<string, array{binary: string, ekstensi: string}> Pictures read from the current row, saved only once the row is valid. */
    private array $gambarTertunda = [];

    /** @var array<int, string> Problems found while reading the current cell. */
    private array $masalahSel = [];

    /** @var array<int, string> Warnings found while reading the current cell. */
    private array $peringatanSel = [];

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

        $this->zip = $zip;
        $this->relasi = $this->bacaRelasi($zip->getFromName('word/_rels/document.xml.rels'));

        $dom = new DOMDocument;
        $dom->loadXML($documentXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS_W);

        $tabel = $xpath->query('//w:tbl');

        if ($tabel === false || $tabel->length === 0) {
            $this->errors[] = 'Tidak ditemukan tabel di dalam file Word. Gunakan template yang disediakan, jangan bikin dokumen baru dari nol.';
            $zip->close();

            return;
        }

        $baris = $xpath->query('./w:tr', $tabel->item(0));

        // Row 0 is the header (Pertanyaan | A | B | C | D | E | Jawaban) — skip it.
        for ($i = 1; $i < $baris->length; $i++) {
            $nomorBaris = $i + 1; // human-facing: header counts as row 1, matching what's visible in the table itself.
            $this->prosesBaris($xpath, $baris->item($i), $nomorBaris);
        }

        $zip->close();
        $this->zip = null;
    }

    private function prosesBaris(DOMXPath $xpath, DOMElement $tr, int $nomorBaris): void
    {
        $sel = $xpath->query('./w:tc', $tr);

        if ($sel->length < 7) {
            $this->errors[] = "Baris {$nomorBaris}: jumlah kolom kurang dari 7 (Pertanyaan, A, B, C, D, E, Jawaban).";

            return;
        }

        $this->gambarTertunda = [];
        $kolom = [];
        $masalah = [];
        $peringatan = [];

        foreach (self::KOLOM as $index => $kunci) {
            /** @var DOMElement $cell */
            $cell = $sel->item($index);

            if ($kunci === 'jawaban') {
                $kolom[$kunci] = trim($cell->textContent);

                continue;
            }

            $this->masalahSel = [];
            $this->peringatanSel = [];
            $kolom[$kunci] = $this->bacaSel($xpath, $cell);

            foreach ($this->masalahSel as $pesan) {
                $masalah[] = "Baris {$nomorBaris}: ".self::NAMA_KOLOM[$kunci]." {$pesan}";
            }

            foreach (array_unique($this->peringatanSel) as $pesan) {
                $peringatan[] = "Baris {$nomorBaris}: ".self::NAMA_KOLOM[$kunci]." {$pesan}";
            }
        }

        if ($masalah !== []) {
            array_push($this->errors, ...$masalah);

            return;
        }

        $jawaban = strtoupper($kolom['jawaban']);

        if (! $kolom['pertanyaan']['adaTeks']) {
            $this->errors[] = "Baris {$nomorBaris}: kolom Pertanyaan harus diisi teks atau rumus (gambar saja tidak cukup).";

            return;
        }

        if (! in_array($jawaban, ['A', 'B', 'C', 'D', 'E'], true)) {
            $this->errors[] = "Baris {$nomorBaris}: kolom Jawaban harus salah satu dari A, B, C, D, atau E.";

            return;
        }

        foreach (['A', 'B', 'C', 'D'] as $huruf) {
            if ($kolom[$huruf]['kosong']) {
                $this->errors[] = "Baris {$nomorBaris}: pilihan {$huruf} harus diisi teks, rumus, atau gambar.";

                return;
            }
        }

        if ($jawaban === 'E' && $kolom['E']['kosong']) {
            $this->errors[] = "Baris {$nomorBaris}: Jawaban E dipilih tapi pilihan E kosong (teks, rumus, maupun gambar).";

            return;
        }

        // Only now — the row is valid — do its pictures get written to storage.
        $url = [];
        foreach ($this->gambarTertunda as $penanda => $gambar) {
            $path = "soal/{$this->bankSoalId}/".Str::random(40).".{$gambar['ekstensi']}";
            Storage::disk('public')->put($path, $gambar['binary']);
            $url[$penanda] = '<img src="'.e(Storage::disk('public')->url($path)).'">';
        }

        $html = fn (string $kunci) => $kolom[$kunci]['kosong'] ? null : SoalContentPurifier::bersihkan(strtr($kolom[$kunci]['html'], $url));

        Soal::create([
            'bank_soal_id' => $this->bankSoalId,
            'pertanyaan' => $html('pertanyaan'),
            'pilih_a' => $html('A'),
            'pilih_b' => $html('B'),
            'pilih_c' => $html('C'),
            'pilih_d' => $html('D'),
            'pilih_e' => $html('E'),
            'jawaban' => $jawaban,
        ]);

        array_push($this->peringatan, ...$peringatan);
        $this->imported++;
    }

    /**
     * One cell → its HTML (one <p> per Word paragraph) plus what it holds.
     *
     * @return array{html: string, adaTeks: bool, kosong: bool}
     */
    private function bacaSel(DOMXPath $xpath, DOMElement $cell): array
    {
        $paragraf = [];
        $adaTeks = false;
        $adaGambar = false;

        foreach ($xpath->query('./w:p | ./w:sdt/w:sdtContent/w:p', $cell) as $p) {
            $isi = ['html' => '', 'teks' => false, 'gambar' => false];
            $this->bacaInline($p, $isi);

            if (trim($isi['html']) !== '') {
                $paragraf[] = '<p>'.trim($isi['html']).'</p>';
            }

            $adaTeks = $adaTeks || $isi['teks'];
            $adaGambar = $adaGambar || $isi['gambar'];
        }

        return [
            'html' => implode('', $paragraf),
            'adaTeks' => $adaTeks,
            'kosong' => ! $adaTeks && ! $adaGambar,
        ];
    }

    /**
     * Walks a paragraph's inline content in order.
     *
     * @param  array{html: string, teks: bool, gambar: bool}  $isi
     */
    private function bacaInline(DOMNode $node, array &$isi): void
    {
        foreach ($node->childNodes as $anak) {
            if (! $anak instanceof DOMElement) {
                continue;
            }

            if ($anak->namespaceURI === OmmlKeMathml::NS_M && in_array($anak->localName, ['oMath', 'oMathPara'], true)) {
                $isi['html'] .= $this->rumus($anak);
                $isi['teks'] = true;

                continue;
            }

            if ($anak->namespaceURI === self::NS_MC && $anak->localName === 'AlternateContent') {
                // Word offers the same thing twice (a modern Choice and a legacy Fallback) — read only one.
                $pilihan = $anak->getElementsByTagNameNS(self::NS_MC, 'Fallback')->item(0)
                    ?? $anak->getElementsByTagNameNS(self::NS_MC, 'Choice')->item(0);

                if ($pilihan) {
                    $this->bacaInline($pilihan, $isi);
                }

                continue;
            }

            if ($anak->namespaceURI !== self::NS_W) {
                continue;
            }

            match ($anak->localName) {
                'r' => $this->bacaRun($anak, $isi),
                // Containers whose runs still belong to the visible text.
                'hyperlink', 'ins', 'smartTag', 'sdt', 'sdtContent', 'fldSimple', 'customXml' => $this->bacaInline($anak, $isi),
                default => null, // pPr, del (deleted text), bookmarks, …
            };
        }
    }

    /**
     * @param  array{html: string, teks: bool, gambar: bool}  $isi
     */
    private function bacaRun(DOMElement $run, array &$isi, ?string $posisi = null): void
    {
        // Superscript/subscript lives on the run's own <w:rPr><w:vertAlign>.
        foreach ($run->childNodes as $anak) {
            if ($anak instanceof DOMElement && $anak->namespaceURI === self::NS_W && $anak->localName === 'rPr') {
                foreach ($anak->childNodes as $properti) {
                    if ($properti instanceof DOMElement && $properti->localName === 'vertAlign') {
                        $posisi = $properti->getAttributeNS(self::NS_W, 'val');
                    }
                }
            }
        }

        foreach ($run->childNodes as $anak) {
            if (! $anak instanceof DOMElement) {
                continue;
            }

            if ($anak->namespaceURI === self::NS_MC && $anak->localName === 'AlternateContent') {
                // A picture offered twice (modern Choice + legacy Fallback) — read only one branch.
                $cabang = $anak->getElementsByTagNameNS(self::NS_MC, 'Fallback')->item(0)
                    ?? $anak->getElementsByTagNameNS(self::NS_MC, 'Choice')->item(0);

                if ($cabang instanceof DOMElement) {
                    $this->bacaRun($cabang, $isi, $posisi);
                }

                continue;
            }

            if ($anak->namespaceURI !== self::NS_W) {
                continue;
            }

            switch ($anak->localName) {
                case 't':
                    if ($anak->textContent === '') {
                        break;
                    }
                    $teks = e($anak->textContent);
                    $isi['html'] .= match ($posisi) {
                        'superscript' => "<sup>{$teks}</sup>",
                        'subscript' => "<sub>{$teks}</sub>",
                        default => $teks,
                    };
                    $isi['teks'] = $isi['teks'] || trim($anak->textContent) !== '';
                    break;
                case 'tab':
                    $isi['html'] .= ' ';
                    break;
                case 'br':
                case 'cr':
                    $isi['html'] .= '<br>';
                    break;
                case 'drawing':
                    $blip = $anak->getElementsByTagNameNS(self::NS_A, 'blip')->item(0);
                    $this->tambahGambar($blip?->getAttributeNS(self::NS_R, 'embed'), $isi);
                    break;
                case 'pict':
                    $imagedata = $anak->getElementsByTagNameNS(self::NS_V, 'imagedata')->item(0);
                    $this->tambahGambar($imagedata?->getAttributeNS(self::NS_R, 'id'), $isi);
                    break;
                case 'object':
                    $this->objekTertanam($anak);
                    break;
            }
        }
    }

    /**
     * An OLE object in a run: a MathType equation (Equation.DSMT4, the old
     * Equation.3 editor, …) or something else entirely (an embedded chart,
     * an Excel range). Neither can be carried over.
     */
    private function objekTertanam(DOMElement $objek): void
    {
        $ole = $objek->getElementsByTagNameNS(self::NS_O, 'OLEObject')->item(0);
        $progId = $ole?->getAttribute('ProgID') ?? '';

        if (Str::startsWith($progId, 'Equation.')) {
            $this->masalahSel[] = 'berisi rumus MathType yang belum didukung. Ubah rumusnya jadi Equation bawaan Word (Insert → Equation), atau sisipkan sebagai gambar PNG/JPG.';

            return;
        }

        $this->peringatanSel[] = 'berisi objek tertanam'.($progId !== '' ? " ({$progId})" : '').' yang tidak ikut diimport — sisipkan sebagai gambar kalau perlu ditampilkan.';
    }

    private function rumus(DOMElement $node): string
    {
        $konverter = new OmmlKeMathml;

        $html = $node->localName === 'oMathPara'
            ? implode('', array_map(
                fn (DOMElement $oMath) => $konverter->konversi($oMath, blok: true),
                iterator_to_array($node->getElementsByTagNameNS(OmmlKeMathml::NS_M, 'oMath')),
            ))
            : $konverter->konversi($node);

        if ($konverter->tidakDikenal !== []) {
            $this->peringatanSel[] = 'berisi bagian rumus yang belum dikenali ('.implode(', ', array_unique($konverter->tidakDikenal)).') — cek tampilannya setelah import.';
        }

        return $html;
    }

    /**
     * Reads the picture behind a relationship id into the current row's
     * pending list and leaves a placeholder where it sits in the HTML.
     *
     * @param  array{html: string, teks: bool, gambar: bool}  $isi
     */
    private function tambahGambar(?string $rId, array &$isi): void
    {
        if (! $rId || ! isset($this->relasi[$rId])) {
            return;
        }

        // Targets in the rels file are relative to word/ (e.g. "media/image1.png").
        $target = 'word/'.ltrim($this->relasi[$rId], '/');
        $binary = $this->zip?->getFromName($target);

        if ($binary === false || $binary === null) {
            return;
        }

        $ekstensi = strtolower(pathinfo($target, PATHINFO_EXTENSION)) ?: 'png';

        if (in_array($ekstensi, self::EKSTENSI_TIDAK_TAMPIL, true)) {
            $this->masalahSel[] = 'berisi gambar format .'.$ekstensi.' yang tidak bisa ditampilkan browser. Simpan ulang gambarnya sebagai PNG/JPG lalu sisipkan lagi.';

            return;
        }

        $penanda = "\u{E000}gambar".count($this->gambarTertunda)."\u{E000}";
        $this->gambarTertunda[$penanda] = ['binary' => $binary, 'ekstensi' => $ekstensi];
        $isi['html'] .= $penanda;
        $isi['gambar'] = true;
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
