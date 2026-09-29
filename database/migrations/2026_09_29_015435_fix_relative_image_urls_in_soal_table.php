<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * TinyMCE (resources/js/soal-editor.js) used to save uploaded gambar with a
 * URL relative to the editor page it was inserted from, e.g.
 * `src="../../../../../storage/soal/9/x.jpeg"`. That only resolves because
 * browsers clamp excess `../` at the domain root — it breaks as soon as the
 * app runs under a subfolder. The editor now keeps URLs root-relative
 * (`relative_urls: false`); this rewrites the already-saved ones to match,
 * using the public disk's own URL path so a subfolder APP_URL is honored.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private const KOLOM_HTML = ['pertanyaan', 'pilih_a', 'pilih_b', 'pilih_c', 'pilih_d', 'pilih_e'];

    public function up(): void
    {
        // e.g. "/storage", or "/olimpiade/storage" under a subfolder APP_URL.
        $storagePath = rtrim(parse_url(Storage::disk('public')->url(''), PHP_URL_PATH) ?? '/storage', '/');

        DB::table('soal')
            ->where(function ($query): void {
                foreach (self::KOLOM_HTML as $kolom) {
                    $query->orWhere($kolom, 'like', '%../storage/%');
                }
            })
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($storagePath): void {
                foreach ($rows as $row) {
                    $perubahan = [];

                    foreach (self::KOLOM_HTML as $kolom) {
                        if ($row->{$kolom} === null) {
                            continue;
                        }

                        $baru = preg_replace('#(src=["\'])(?:\.\./)+storage/#', '$1'.$storagePath.'/', $row->{$kolom});

                        if ($baru !== $row->{$kolom}) {
                            $perubahan[$kolom] = $baru;
                        }
                    }

                    if ($perubahan !== []) {
                        DB::table('soal')->where('id', $row->id)->update($perubahan);
                    }
                }
            });
    }

    /**
     * Nothing to restore: the old relative URLs pointed at the same files.
     */
    public function down(): void
    {
        //
    }
};
