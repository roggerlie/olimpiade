<?php

namespace Database\Seeders\Gambar;

/**
 * Hand-drawn SVG diagrams for SoalIpaSdSeeder. Plain SVG strings, generated
 * from the soal's own numbers so the picture and the answer key can't drift
 * apart — no image downloads, no font files, identical on Windows and Linux.
 * Rendered via a normal <img>, so nothing in them can run script.
 */
class IpaSdGambar
{
    private const TEKS = '#1f2937';

    private const GARIS = '#374151';

    public static function termometer(int $suhu): string
    {
        $y = fn (float $c) => 250 - $c * 4.2;
        $isi = '<rect x="95" y="20" width="30" height="245" rx="15" fill="#f3f4f6" stroke="'.self::GARIS.'" stroke-width="2"/>';
        $isi .= '<rect x="103" y="'.$y($suhu).'" width="14" height="'.(275 - $y($suhu)).'" fill="#ef4444"/>';
        $isi .= '<circle cx="110" cy="277" r="24" fill="#ef4444" stroke="'.self::GARIS.'" stroke-width="2"/>';

        for ($c = 0; $c <= 50; $c++) {
            $panjang = $c % 10 === 0 ? 16 : ($c % 5 === 0 ? 11 : 6);
            $isi .= self::garis(125, $y($c), 125 + $panjang, $y($c), self::GARIS, 1);

            if ($c % 10 === 0) {
                $isi .= self::teks(150, $y($c) + 5, (string) $c, 13, 'start');
            }
        }

        $isi .= self::teks(160, 18, '°C', 14, 'start', weight: 'bold');

        return self::svg(220, 320, $isi);
    }

    /**
     * @param  list<int>  $tinggi  Plant height (cm) for hari 1..5, each 0..20.
     */
    public static function grafikTanaman(array $tinggi): string
    {
        $y = fn (float $cm) => 250 - $cm * 10;
        $isi = self::teks(260, 24, 'Pertumbuhan Tanaman Kacang Hijau', 15, weight: 'bold');

        for ($cm = 0; $cm <= 20; $cm += 2) {
            $isi .= self::garis(70, $y($cm), 450, $y($cm), '#e5e7eb', 1);
            $isi .= self::teks(62, $y($cm) + 4, (string) $cm, 12, 'end');
        }

        foreach ($tinggi as $i => $cm) {
            $x = 95 + $i * 72;
            $isi .= '<rect x="'.$x.'" y="'.$y($cm).'" width="40" height="'.($cm * 10).'" fill="#22c55e"/>';
            $isi .= self::teks($x + 20, 270, 'Hari '.($i + 1), 12);
        }

        $isi .= self::garis(70, 250, 450, 250).self::garis(70, 40, 70, 250);
        $isi .= '<text x="22" y="145" font-size="12" fill="'.self::TEKS.'" text-anchor="middle" transform="rotate(-90 22 145)">Tinggi (cm)</text>';

        return self::svg(470, 290, $isi);
    }

    /**
     * @param  'seri'|'paralel'  $jenis
     */
    public static function rangkaianListrik(string $jenis, int $jumlahLampu, bool $saklarTerbuka): string
    {
        $isi = '';

        if ($jenis === 'seri') {
            $kanan = 420;
            $isi .= self::garis(60, 60, $kanan, 60).self::garis($kanan, 60, $kanan, 220);

            for ($i = 1; $i <= $jumlahLampu; $i++) {
                $x = 180 + ($kanan - 180) * $i / ($jumlahLampu + 1);
                $isi .= self::lampu($x, 60, "L{$i}", $x, 32);
            }
        } else {
            $posisi = $jumlahLampu === 2 ? [260, 400] : [220, 320, 420];
            $kanan = end($posisi);
            $isi .= self::garis(60, 60, $kanan, 60);

            foreach ($posisi as $i => $x) {
                $isi .= self::garis($x, 60, $x, 220).self::lampu($x, 140, 'L'.($i + 1), $x + 26, 145, 'start');
            }
        }

        // Baterai on the left edge: long plate (+) above, short thick plate below.
        $isi .= self::garis(60, 60, 60, 130).self::garis(60, 146, 60, 220);
        $isi .= self::garis(38, 130, 82, 130, self::GARIS, 2).self::garis(48, 146, 72, 146, self::GARIS, 6);
        $isi .= self::teks(92, 128, '+', 16, 'start', weight: 'bold');
        $isi .= '<text x="22" y="140" font-size="12" fill="'.self::TEKS.'" text-anchor="middle" transform="rotate(-90 22 140)">Baterai</text>';

        // Saklar on the bottom wire.
        $isi .= self::garis(60, 220, 110, 220).self::garis(170, 220, $kanan, 220);
        $isi .= $saklarTerbuka ? self::garis(110, 220, 162, 190, self::GARIS, 3) : self::garis(110, 220, 170, 220, self::GARIS, 3);
        $isi .= '<circle cx="110" cy="220" r="4" fill="'.self::GARIS.'"/><circle cx="170" cy="220" r="4" fill="'.self::GARIS.'"/>';
        $isi .= self::teks(140, 248, $saklarTerbuka ? 'Saklar (terbuka)' : 'Saklar (tertutup)', 12);

        return self::svg(480, 265, $isi);
    }

    public static function gelasUkur(int $volume): string
    {
        return self::svg(260, 310, self::gelas(90, $volume));
    }

    public static function gelasUkurDenganBatu(int $sebelum, int $sesudah): string
    {
        $isi = self::gelas(60, $sebelum, caption: 'Sebelum');
        $isi .= self::gelas(280, $sesudah, batu: true, caption: 'Sesudah batu dimasukkan');

        return self::svg(470, 330, $isi);
    }

    /**
     * @param  list<string>  $makhluk
     */
    public static function rantaiMakanan(array $makhluk): string
    {
        $lebar = 100;
        $jarak = 44;
        $total = count($makhluk) * $lebar + (count($makhluk) - 1) * $jarak;
        $x = (580 - $total) / 2;
        $isi = self::defsPanah();

        foreach ($makhluk as $i => $nama) {
            $isi .= '<rect x="'.$x.'" y="40" width="'.$lebar.'" height="46" rx="10" fill="#ecfdf5" stroke="#059669" stroke-width="2"/>';
            $isi .= self::teks($x + $lebar / 2, 68, $nama, 13, weight: 'bold');

            if ($i < count($makhluk) - 1) {
                $isi .= self::garis($x + $lebar + 4, 63, $x + $lebar + $jarak - 6, 63, self::GARIS, 2, 'panah-abu');
            }

            $x += $lebar + $jarak;
        }

        $isi .= self::teks(290, 118, 'Tanda panah (→) berarti "dimakan oleh"', 12, fill: '#6b7280');

        return self::svg(580, 135, $isi);
    }

    /**
     * @param  list<int>  $anakTimbangan  Weights (gram) on the right pan.
     */
    public static function neraca(string $benda, array $anakTimbangan): string
    {
        $isi = '<polygon points="200,262 260,262 230,240" fill="#9ca3af"/>'.self::garis(230, 240, 230, 80, self::GARIS, 5);
        $isi .= self::garis(80, 80, 380, 80, self::GARIS, 5).'<circle cx="230" cy="80" r="7" fill="'.self::GARIS.'"/>';

        foreach ([110, 350] as $cx) {
            $isi .= self::garis($cx, 80, $cx - 60, 180, '#6b7280', 1).self::garis($cx, 80, $cx + 60, 180, '#6b7280', 1);
            $isi .= '<path d="M'.($cx - 75).' 180 Q'.$cx.' 205 '.($cx + 75).' 180 Z" fill="#d1d5db" stroke="'.self::GARIS.'" stroke-width="2"/>';
        }

        $isi .= '<circle cx="110" cy="152" r="26" fill="#fca5a5" stroke="#b91c1c" stroke-width="2"/>';
        $isi .= self::teks(110, 157, $benda, 12, weight: 'bold');

        $x = 350 - (count($anakTimbangan) * 34 - 4) / 2;

        foreach ($anakTimbangan as $gram) {
            $tinggi = min(44, 22 + $gram / 25);
            $isi .= '<rect x="'.$x.'" y="'.(178 - $tinggi).'" width="30" height="'.$tinggi.'" rx="3" fill="#fbbf24" stroke="#92400e" stroke-width="1.5"/>';
            $isi .= self::teks($x + 15, 174 - $tinggi / 2 + 4, "{$gram} g", 9, weight: 'bold');
            $x += 34;
        }

        return self::svg(460, 280, $isi);
    }

    /**
     * @param  array{0: 'U'|'S', 1: 'U'|'S'}  $kiri  Poles of the left magnet, left to right.
     * @param  array{0: 'U'|'S', 1: 'U'|'S'}  $kanan  Poles of the right magnet, left to right.
     */
    public static function duaMagnet(array $kiri, array $kanan): string
    {
        $isi = self::magnet(30, 50, $kiri).self::magnet(270, 50, $kanan);
        $isi .= self::teks(220, 135, 'U = kutub utara, S = kutub selatan', 12, fill: '#6b7280');

        return self::svg(440, 150, $isi);
    }

    /**
     * @param  list<string>  $benda
     */
    public static function magnetDanBenda(array $benda): string
    {
        $isi = self::magnet(170, 20, ['U', 'S']);

        foreach ($benda as $i => $nama) {
            $x = 20 + $i * 120;
            $isi .= '<rect x="'.$x.'" y="100" width="105" height="50" rx="8" fill="#f9fafb" stroke="#9ca3af" stroke-width="1.5"/>';
            $isi .= self::teks($x + 52, 130, $nama, 12);
        }

        return self::svg(500, 165, $isi);
    }

    /**
     * Padat/Cair/Gas triangle with all six changes drawn; the asked-about
     * one is red, the rest gray.
     *
     * @param  array{0: string, 1: string}  $sorot  [dari, ke], e.g. ['Padat', 'Cair'].
     */
    public static function perubahanWujud(array $sorot): string
    {
        $titik = ['Gas' => [250, 60], 'Padat' => [100, 250], 'Cair' => [400, 250]];
        $isi = self::defsPanah();

        foreach ([['Padat', 'Cair'], ['Cair', 'Padat'], ['Cair', 'Gas'], ['Gas', 'Cair'], ['Padat', 'Gas'], ['Gas', 'Padat']] as [$dari, $ke]) {
            [$x1, $y1] = $titik[$dari];
            [$x2, $y2] = $titik[$ke];
            $panjang = hypot($x2 - $x1, $y2 - $y1);
            [$ux, $uy] = [($x2 - $x1) / $panjang, ($y2 - $y1) / $panjang];
            // Each direction sits on its own side of the edge, 9px off-center.
            [$nx, $ny] = [-$uy * 9, $ux * 9];
            $merah = [$dari, $ke] === $sorot;

            $isi .= self::garis(
                $x1 + $ux * 50 + $nx, $y1 + $uy * 50 + $ny,
                $x2 - $ux * 56 + $nx, $y2 - $uy * 56 + $ny,
                $merah ? '#dc2626' : '#9ca3af', $merah ? 4 : 2, $merah ? 'panah-merah' : 'panah-abu',
            );
        }

        foreach ($titik as $nama => [$x, $y]) {
            $isi .= '<circle cx="'.$x.'" cy="'.$y.'" r="40" fill="#eff6ff" stroke="#2563eb" stroke-width="2"/>';
            $isi .= self::teks($x, $y + 5, $nama, 15, weight: 'bold');
        }

        return self::svg(500, 310, $isi);
    }

    public static function gerakMobil(string $jarak, string $waktu): string
    {
        $isi = self::defsPanah();
        $isi .= '<rect x="0" y="170" width="480" height="44" fill="#6b7280"/>';
        $isi .= '<line x1="0" y1="192" x2="480" y2="192" stroke="#fff" stroke-width="3" stroke-dasharray="18 14"/>';

        foreach ([[60, 'Start'], [420, 'Finish']] as [$x, $label]) {
            $isi .= self::garis($x, 170, $x, 100, self::GARIS, 3);
            $isi .= '<polygon points="'.$x.',100 '.($x + 30).',110 '.$x.',120" fill="'.($label === 'Start' ? '#22c55e' : '#ef4444').'"/>';
            $isi .= self::teks($x, 232, $label, 12, weight: 'bold');
        }

        // Mobil, parked at the start line.
        $isi .= '<rect x="70" y="140" width="90" height="26" rx="6" fill="#2563eb"/><rect x="88" y="122" width="50" height="22" rx="6" fill="#60a5fa"/>';
        $isi .= '<circle cx="92" cy="168" r="10" fill="#111827"/><circle cx="140" cy="168" r="10" fill="#111827"/>';

        $isi .= self::garis(66, 70, 414, 70, self::GARIS, 1.5, 'panah-abu', 'panah-abu');
        $isi .= self::teks(240, 60, "Jarak = {$jarak}", 14, weight: 'bold');
        $isi .= self::teks(240, 30, "Waktu tempuh = {$waktu}", 14, fill: '#2563eb', weight: 'bold');

        return self::svg(480, 245, $isi);
    }

    private static function gelas(float $x, int $volume, bool $batu = false, ?string $caption = null): string
    {
        $y = fn (float $ml) => 270 - $ml * 2.2;
        $isi = '<rect x="'.$x.'" y="'.$y($volume).'" width="80" height="'.($volume * 2.2).'" fill="#bfdbfe"/>';

        if ($batu) {
            $isi .= '<polygon points="'.($x + 18).',270 '.($x + 14).',252 '.($x + 30).',236 '.($x + 56).',240 '.($x + 64).',258 '.($x + 58).',270" fill="#78716c" stroke="#44403c" stroke-width="1.5"/>';
        }

        $isi .= '<path d="M'.$x.' 40 L'.$x.' 270 L'.($x + 80).' 270 L'.($x + 80).' 40" fill="none" stroke="'.self::GARIS.'" stroke-width="2.5"/>';
        $isi .= self::garis($x - 10, 272, $x + 90, 272, self::GARIS, 4);

        for ($ml = 0; $ml <= 100; $ml += 5) {
            $isi .= self::garis($x + 80, $y($ml), $x + 80 - ($ml % 10 === 0 ? 16 : 9), $y($ml), self::GARIS, 1);

            if ($ml % 10 === 0) {
                $isi .= self::teks($x + 88, $y($ml) + 4, (string) $ml, 11, 'start');
            }
        }

        $isi .= self::teks($x + 88, 30, 'mL', 12, 'start', weight: 'bold');

        if ($caption) {
            $isi .= self::teks($x + 40, 300, $caption, 12, weight: 'bold');
        }

        return $isi;
    }

    /**
     * @param  array{0: string, 1: string}  $kutub
     */
    private static function magnet(float $x, float $y, array $kutub): string
    {
        $isi = '';

        foreach ($kutub as $i => $k) {
            $isi .= '<rect x="'.($x + $i * 70).'" y="'.$y.'" width="70" height="44" fill="'.($k === 'U' ? '#ef4444' : '#3b82f6').'"/>';
            $isi .= self::teks($x + $i * 70 + 35, $y + 29, $k, 20, fill: '#fff', weight: 'bold');
        }

        return $isi.'<rect x="'.$x.'" y="'.$y.'" width="140" height="44" fill="none" stroke="'.self::GARIS.'" stroke-width="2"/>';
    }

    private static function lampu(float $x, float $y, string $label, float $lx, float $ly, string $anchor = 'middle'): string
    {
        return '<circle cx="'.$x.'" cy="'.$y.'" r="16" fill="#fef9c3" stroke="'.self::GARIS.'" stroke-width="2"/>'
            .self::garis($x - 11, $y - 11, $x + 11, $y + 11, self::GARIS, 2).self::garis($x - 11, $y + 11, $x + 11, $y - 11, self::GARIS, 2)
            .self::teks($lx, $ly, $label, 13, $anchor, weight: 'bold');
    }

    private static function defsPanah(): string
    {
        $marker = fn (string $id, string $warna) => '<marker id="'.$id.'" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="'.$warna.'"/></marker>';

        return '<defs>'.$marker('panah-abu', '#6b7280').$marker('panah-merah', '#dc2626').'</defs>';
    }

    private static function garis(float $x1, float $y1, float $x2, float $y2, string $warna = self::GARIS, float $tebal = 2, ?string $panahAkhir = null, ?string $panahAwal = null): string
    {
        return '<line x1="'.round($x1, 1).'" y1="'.round($y1, 1).'" x2="'.round($x2, 1).'" y2="'.round($y2, 1).'" stroke="'.$warna.'" stroke-width="'.$tebal.'"'
            .($panahAkhir ? ' marker-end="url(#'.$panahAkhir.')"' : '')
            .($panahAwal ? ' marker-start="url(#'.$panahAwal.')"' : '')
            .'/>';
    }

    private static function teks(float $x, float $y, string $teks, int $ukuran = 14, string $anchor = 'middle', string $fill = self::TEKS, string $weight = 'normal'): string
    {
        return '<text x="'.round($x, 1).'" y="'.round($y, 1).'" font-size="'.$ukuran.'" font-weight="'.$weight.'" fill="'.$fill.'" text-anchor="'.$anchor.'">'
            .htmlspecialchars($teks, ENT_XML1).'</text>';
    }

    private static function svg(int $lebar, int $tinggi, string $isi): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$lebar.'" height="'.$tinggi.'" viewBox="0 0 '.$lebar.' '.$tinggi.'" font-family="Arial, Helvetica, sans-serif">'
            .'<rect width="100%" height="100%" fill="#ffffff"/>'.$isi.'</svg>';
    }
}
