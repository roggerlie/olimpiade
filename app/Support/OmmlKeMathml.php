<?php

namespace App\Support;

use DOMElement;
use DOMNode;

/**
 * Converts a Word equation (Office Math Markup, <m:oMath>) into MathML — the
 * <math> markup current Chrome/Edge/Firefox render natively, which is what
 * App\Imports\SoalWordImport stores so a formula typed with Word's own
 * Insert → Equation survives the import instead of silently vanishing.
 *
 * Covers the structures an olympiad soal realistically uses: fractions,
 * super/subscripts, roots, brackets, n-ary operators (∑ ∫ ∏), functions,
 * limits, accents/bars, equation arrays and matrices. Anything else is
 * walked through as a plain row of its children (so its text still shows)
 * and recorded in $tidakDikenal, letting the importer warn the admin to
 * double-check that soal rather than pretend it came through perfectly.
 *
 * Every piece of text is escaped here; the output is still run through
 * SoalContentPurifier afterwards like any other soal HTML.
 */
class OmmlKeMathml
{
    public const NS_M = 'http://schemas.openxmlformats.org/officeDocument/2006/math';

    public const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** @var array<int, string> OMML element names met but not specifically understood. */
    public array $tidakDikenal = [];

    /**
     * Wrappers whose only job is to hold child content — rendered as an
     * <mrow> of their children.
     */
    private const WADAH = ['e', 'num', 'den', 'sub', 'sup', 'deg', 'lim', 'fName', 'box', 'borderBox', 'phant', 'oMath', 'argPr'];

    /**
     * Property elements (…Pr) carry settings, never content.
     */
    private const PROPERTI = ['rPr', 'ctrlPr', 'fPr', 'sSupPr', 'sSubPr', 'sSubSupPr', 'sPrePr', 'radPr', 'dPr', 'naryPr', 'funcPr',
        'limLowPr', 'limUppPr', 'accPr', 'barPr', 'groupChrPr', 'eqArrPr', 'mPr', 'mcs', 'boxPr', 'borderBoxPr', 'phantPr', 'oMathParaPr', 'mrPr'];

    /**
     * One <m:oMath> → one <math> element.
     */
    public function konversi(DOMElement $oMath, bool $blok = false): string
    {
        $isi = $this->anak($oMath);

        return '<math'.($blok ? ' display="block"' : '').'>'.$isi.'</math>';
    }

    private function node(DOMNode $node): string
    {
        if (! $node instanceof DOMElement) {
            return '';
        }

        // Plain Word runs can sit inside an equation (e.g. text typed in "normal text" mode).
        if ($node->namespaceURI === self::NS_W) {
            return $node->localName === 't' ? '<mtext>'.$this->esc($node->textContent).'</mtext>' : $this->anak($node);
        }

        if ($node->namespaceURI !== self::NS_M) {
            return '';
        }

        $nama = $node->localName;

        if (in_array($nama, self::PROPERTI, true)) {
            return '';
        }

        if (in_array($nama, self::WADAH, true)) {
            return $this->baris($node);
        }

        return match ($nama) {
            'r' => $this->run($node),
            'f' => $this->pecahan($node),
            'sSup' => '<msup>'.$this->bagian($node, 'e').$this->bagian($node, 'sup').'</msup>',
            'sSub' => '<msub>'.$this->bagian($node, 'e').$this->bagian($node, 'sub').'</msub>',
            'sSubSup' => '<msubsup>'.$this->bagian($node, 'e').$this->bagian($node, 'sub').$this->bagian($node, 'sup').'</msubsup>',
            'sPre' => '<mrow><msubsup>'.$this->kosong().$this->bagian($node, 'sub').$this->bagian($node, 'sup').'</msubsup>'.$this->bagian($node, 'e').'</mrow>',
            'rad' => $this->akar($node),
            'd' => $this->kurung($node),
            'nary' => $this->nAry($node),
            'func' => '<mrow>'.$this->bagian($node, 'fName').'<mo>&#x2061;</mo>'.$this->bagian($node, 'e').'</mrow>',
            'limLow' => '<munder>'.$this->bagian($node, 'e').$this->bagian($node, 'lim').'</munder>',
            'limUpp' => '<mover>'.$this->bagian($node, 'e').$this->bagian($node, 'lim').'</mover>',
            'acc' => '<mover accent="true">'.$this->bagian($node, 'e').'<mo>'.$this->esc($this->karakter($node, 'accPr', "\u{0302}")).'</mo></mover>',
            'bar' => $this->garis($node),
            'groupChr' => $this->kelompok($node),
            'eqArr' => $this->tabel(array_map(fn (DOMElement $e) => [$e], $this->anakBernama($node, 'e'))),
            'm' => $this->tabel(array_map(fn (DOMElement $mr) => $this->anakBernama($mr, 'e'), $this->anakBernama($node, 'mr'))),
            'oMathPara' => $this->anak($node),
            default => $this->tidakDikenali($node),
        };
    }

    private function anak(DOMNode $node): string
    {
        $hasil = '';

        foreach ($node->childNodes as $anak) {
            $hasil .= $this->node($anak);
        }

        return $hasil;
    }

    /**
     * Children as one <mrow> — never empty, since an empty child would
     * break the fixed arity of e.g. <msup>/<mfrac> (and HTMLPurifier's
     * RemoveEmpty would drop it).
     */
    private function baris(DOMNode $node): string
    {
        $isi = $this->anak($node);

        return '<mrow>'.($isi === '' ? $this->kosong() : $isi).'</mrow>';
    }

    private function bagian(DOMElement $induk, string $nama): string
    {
        $anak = $this->anakBernama($induk, $nama);

        return $anak === [] ? '<mrow>'.$this->kosong().'</mrow>' : $this->baris($anak[0]);
    }

    /**
     * @return array<int, DOMElement>
     */
    private function anakBernama(DOMElement $induk, string $nama): array
    {
        $hasil = [];

        foreach ($induk->childNodes as $anak) {
            if ($anak instanceof DOMElement && $anak->namespaceURI === self::NS_M && $anak->localName === $nama) {
                $hasil[] = $anak;
            }
        }

        return $hasil;
    }

    /**
     * The `m:val` of a property, e.g. <m:dPr><m:begChr m:val="["/></m:dPr>.
     * Null when absent; '' when present but empty (meaning "no character").
     */
    private function properti(DOMElement $induk, string $wadahPr, string $nama): ?string
    {
        foreach ($this->anakBernama($induk, $wadahPr) as $pr) {
            foreach ($this->anakBernama($pr, $nama) as $el) {
                return $el->hasAttributeNS(self::NS_M, 'val') ? $el->getAttributeNS(self::NS_M, 'val') : '';
            }
        }

        return null;
    }

    private function aktif(DOMElement $induk, string $wadahPr, string $nama): bool
    {
        $nilai = $this->properti($induk, $wadahPr, $nama);

        return $nilai !== null && ! in_array(strtolower($nilai), ['0', 'off', 'false'], true);
    }

    private function karakter(DOMElement $induk, string $wadahPr, string $bawaan): string
    {
        return $this->properti($induk, $wadahPr, 'chr') ?? $bawaan;
    }

    /**
     * A text run: numbers → <mn>, single letters → <mi>, anything else →
     * <mo>. "Normal text" runs (m:rPr/m:nor) stay one <mtext>.
     */
    private function run(DOMElement $r): string
    {
        $teks = '';

        foreach ($r->childNodes as $anak) {
            if ($anak instanceof DOMElement && $anak->localName === 't') {
                $teks .= $anak->textContent;
            }
        }

        if ($teks === '') {
            return '';
        }

        if ($this->aktif($r, 'rPr', 'nor')) {
            return '<mtext>'.$this->esc($teks).'</mtext>';
        }

        preg_match_all('/\d+(?:[.,]\d+)*|\p{L}|\s+|./u', $teks, $potongan);

        $hasil = '';

        foreach ($potongan[0] as $p) {
            $hasil .= match (true) {
                trim($p) === '' => '',
                (bool) preg_match('/^\d/', $p) => '<mn>'.$this->esc($p).'</mn>',
                (bool) preg_match('/^\p{L}$/u', $p) => '<mi>'.$this->esc($p).'</mi>',
                default => '<mo>'.$this->esc($p).'</mo>',
            };
        }

        return $hasil;
    }

    private function pecahan(DOMElement $f): string
    {
        $pembilang = $this->bagian($f, 'num');
        $penyebut = $this->bagian($f, 'den');

        return match ($this->properti($f, 'fPr', 'type')) {
            'lin' => '<mrow>'.$pembilang.'<mo>/</mo>'.$penyebut.'</mrow>',
            'noBar' => '<mfrac linethickness="0">'.$pembilang.$penyebut.'</mfrac>',
            default => '<mfrac>'.$pembilang.$penyebut.'</mfrac>',
        };
    }

    private function akar(DOMElement $rad): string
    {
        $derajat = $this->anakBernama($rad, 'deg');
        $adaDerajat = $derajat !== [] && trim($derajat[0]->textContent) !== '' && ! $this->aktif($rad, 'radPr', 'degHide');

        return $adaDerajat
            ? '<mroot>'.$this->bagian($rad, 'e').$this->baris($derajat[0]).'</mroot>'
            : '<msqrt>'.$this->bagian($rad, 'e').'</msqrt>';
    }

    private function kurung(DOMElement $d): string
    {
        $buka = $this->properti($d, 'dPr', 'begChr') ?? '(';
        $tutup = $this->properti($d, 'dPr', 'endChr') ?? ')';
        $pemisah = $this->properti($d, 'dPr', 'sepChr') ?? '|';

        $isi = array_map(fn (DOMElement $e) => $this->baris($e), $this->anakBernama($d, 'e'));

        return '<mrow>'
            .($buka !== '' ? '<mo>'.$this->esc($buka).'</mo>' : '')
            .implode('<mo>'.$this->esc($pemisah).'</mo>', $isi)
            .($tutup !== '' ? '<mo>'.$this->esc($tutup).'</mo>' : '')
            .'</mrow>';
    }

    private function nAry(DOMElement $nary): string
    {
        $operator = '<mo>'.$this->esc($this->karakter($nary, 'naryPr', '∫')).'</mo>';
        $bawah = $this->aktif($nary, 'naryPr', 'subHide') ? null : $this->bagian($nary, 'sub');
        $atas = $this->aktif($nary, 'naryPr', 'supHide') ? null : $this->bagian($nary, 'sup');
        $diAtasBawah = $this->properti($nary, 'naryPr', 'limLoc') === 'undOvr';

        $dasar = match (true) {
            $bawah !== null && $atas !== null => $diAtasBawah ? "<munderover>{$operator}{$bawah}{$atas}</munderover>" : "<msubsup>{$operator}{$bawah}{$atas}</msubsup>",
            $bawah !== null => $diAtasBawah ? "<munder>{$operator}{$bawah}</munder>" : "<msub>{$operator}{$bawah}</msub>",
            $atas !== null => $diAtasBawah ? "<mover>{$operator}{$atas}</mover>" : "<msup>{$operator}{$atas}</msup>",
            default => $operator,
        };

        return '<mrow>'.$dasar.$this->bagian($nary, 'e').'</mrow>';
    }

    private function garis(DOMElement $bar): string
    {
        return $this->properti($bar, 'barPr', 'pos') === 'bot'
            ? '<munder accentunder="true">'.$this->bagian($bar, 'e').'<mo>_</mo></munder>'
            : '<mover accent="true">'.$this->bagian($bar, 'e').'<mo>&#x203E;</mo></mover>';
    }

    private function kelompok(DOMElement $group): string
    {
        $karakter = '<mo>'.$this->esc($this->karakter($group, 'groupChrPr', '⏟')).'</mo>';

        return $this->properti($group, 'groupChrPr', 'pos') === 'top'
            ? '<mover>'.$this->bagian($group, 'e').$karakter.'</mover>'
            : '<munder>'.$this->bagian($group, 'e').$karakter.'</munder>';
    }

    /**
     * @param  array<int, array<int, DOMElement>>  $baris
     */
    private function tabel(array $baris): string
    {
        $html = '';

        foreach ($baris as $sel) {
            $html .= '<mtr>'.implode('', array_map(fn (DOMElement $e) => '<mtd>'.$this->baris($e).'</mtd>', $sel)).'</mtr>';
        }

        return '<mtable>'.$html.'</mtable>';
    }

    private function tidakDikenali(DOMElement $node): string
    {
        $this->tidakDikenal[] = $node->localName;

        return $this->baris($node);
    }

    /**
     * Placeholder for a slot Word left empty (e.g. an unfilled exponent box).
     */
    private function kosong(): string
    {
        return '<mi>&#x200B;</mi>';
    }

    private function esc(string $teks): string
    {
        return htmlspecialchars($teks, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
