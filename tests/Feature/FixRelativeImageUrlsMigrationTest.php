<?php

use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// RefreshDatabase has already run this migration on an empty table, so it's
// re-run here by hand against rows seeded afterwards.

function jalankanMigrasiPerbaikanUrlGambar(): void
{
    (require database_path('migrations/2026_09_29_015435_fix_relative_image_urls_in_soal_table.php'))->up();
}

test('it rewrites page-relative gambar URLs to root-relative ones', function () {
    $soal = Soal::factory()->create([
        'pertanyaan' => '<p>Soal</p><p><img src="../../../../../storage/soal/9/a.jpeg" alt="a"></p>',
        'pilih_b' => '<p><img src="../../storage/soal/9/b.png" alt="b"></p>',
    ]);

    jalankanMigrasiPerbaikanUrlGambar();

    $soal->refresh();
    expect($soal->pertanyaan)->toBe('<p>Soal</p><p><img src="/storage/soal/9/a.jpeg" alt="a"></p>')
        ->and($soal->pilih_b)->toBe('<p><img src="/storage/soal/9/b.png" alt="b"></p>');
});

test('it leaves soal without relative gambar URLs untouched', function () {
    $html = '<p>Teks biasa</p><p><img src="/storage/soal/9/c.jpeg" alt="c"></p>';
    $soal = Soal::factory()->create(['pertanyaan' => $html, 'pilih_e' => null]);

    jalankanMigrasiPerbaikanUrlGambar();

    $soal->refresh();
    expect($soal->pertanyaan)->toBe($html)
        ->and($soal->pilih_e)->toBeNull();
});
