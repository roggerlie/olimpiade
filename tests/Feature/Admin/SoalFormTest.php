<?php

use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function baseSoalPayload(): array
{
    return [
        'pertanyaan' => 'Berapa 2 + 2?',
        'pilihA' => '3',
        'pilihB' => '4',
        'pilihC' => '5',
        'pilihD' => '6',
        'jawaban' => 'B',
    ];
}

test('it validates required fields before creating', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->call('save')
        ->assertHasErrors(['pertanyaan', 'pilihA', 'pilihB', 'pilihC', 'pilihD', 'jawaban']);

    expect(Soal::count())->toBe(0);
});

test('it creates a new soal scoped to the bank soal and redirects back to the list', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set(baseSoalPayload())
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.bank-soal.soal', $bankSoal->id));

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal)->not->toBeNull()
        ->and($soal->jawaban)->toBe('B')
        ->and(session('status'))->toBe('Soal ditambahkan.');
});

test('it rejects jawaban E when pilihan E is empty', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set(array_merge(baseSoalPayload(), ['jawaban' => 'E']))
        ->call('save')
        ->assertHasErrors(['jawaban']);

    expect(Soal::count())->toBe(0);
});

test('it accepts jawaban E when pilihan E is filled', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set(array_merge(baseSoalPayload(), ['pilihE' => '7', 'jawaban' => 'E']))
        ->call('save')
        ->assertHasNoErrors();

    expect(Soal::where('bank_soal_id', $bankSoal->id)->where('jawaban', 'E')->exists())->toBeTrue();
});

test('it prefills from an existing soal and updates it, redirecting back with a status message', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();
    $soal = Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => 'Lama']);

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id, 'soal' => $soal])
        ->assertSet('pertanyaan', 'Lama')
        ->set('pertanyaan', 'Baru')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.bank-soal.soal', $bankSoal->id));

    // HTMLPurifier's AutoFormat.AutoParagraph wraps bare text in <p> — the
    // same normalization TinyMCE itself applies to plain typed text, so this
    // matches what a real save from the editor would actually persist.
    expect($soal->fresh()->pertanyaan)->toBe('<p>Baru</p>')
        ->and(session('status'))->toBe('Soal diperbarui.');
});

test('tandaiJawaban marks the clicked pilihan as the answer', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->assertSet('jawaban', '')
        ->call('tandaiJawaban', 'C')
        ->assertSet('jawaban', 'C')
        ->call('tandaiJawaban', 'A')
        ->assertSet('jawaban', 'A');
});

test('a soal created via tandaiJawaban persists the right jawaban', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set([
            'pertanyaan' => 'Berapa 2 + 2?',
            'pilihA' => '3',
            'pilihB' => '4',
            'pilihC' => '5',
            'pilihD' => '6',
        ])
        ->call('tandaiJawaban', 'B')
        ->call('save')
        ->assertHasNoErrors();

    expect(Soal::where('bank_soal_id', $bankSoal->id)->first()->jawaban)->toBe('B');
});

test('a required pilihan with neither text nor image fails validation', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set(array_merge(baseSoalPayload(), ['pilihA' => '']))
        ->call('save')
        ->assertHasErrors(['pilihA']);

    expect(Soal::count())->toBe(0);
});

test('a pilihan that is only an empty paragraph (what TinyMCE leaves behind after clearing text) still fails validation', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set(array_merge(baseSoalPayload(), ['pilihA' => '<p><br></p>']))
        ->call('save')
        ->assertHasErrors(['pilihA']);

    expect(Soal::count())->toBe(0);
});

test('a pilihan can be image-only, embedded inline with no visible text', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set('pertanyaan', 'Mana yang berbentuk segitiga?')
        ->set('pilihA', '<p><img src="/storage/soal/1/segitiga.jpg" width="100" height="80"></p>')
        ->set('pilihB', 'Lingkaran')
        ->set('pilihC', 'Kotak')
        ->set('pilihD', 'Oval')
        ->call('tandaiJawaban', 'A')
        ->call('save')
        ->assertHasNoErrors();

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal->pilih_a)->toContain('<img');
});

test('it strips a script tag and event handler attribute from soal content before saving', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set(array_merge(baseSoalPayload(), [
            'pertanyaan' => '<p>Halo <script>alert(1)</script>dunia <img src="x.png" onerror="alert(1)"></p>',
        ]))
        ->call('save')
        ->assertHasNoErrors();

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal->pertanyaan)->not->toContain('<script')
        ->and($soal->pertanyaan)->not->toContain('onerror')
        ->and($soal->pertanyaan)->toContain('Halo')
        ->and($soal->pertanyaan)->toContain('<img');
});

test('it preserves rich formatting tags tinymce can actually produce', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set(array_merge(baseSoalPayload(), [
            'pertanyaan' => '<p>Jika x = <sup>2</sup>, berapa <strong>hasil</strong>-nya?</p><table><tbody><tr><td>1</td></tr></tbody></table>',
        ]))
        ->call('save')
        ->assertHasNoErrors();

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal->pertanyaan)->toContain('<sup>2</sup>')
        ->and($soal->pertanyaan)->toContain('<strong>hasil</strong>')
        ->and($soal->pertanyaan)->toContain('<table>');
});

test('it preserves a WIRIS MathType formula (SVG data URI image) in soal content', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    // What MathType (resources/js/soal-editor.js) actually saves for a
    // formula — an inline SVG data URI, since its default output format
    // (wirisimageformat) is svg and this app uses WIRIS's cloud service
    // directly rather than a self-hosted backend that could override it.
    $svg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="13" height="19"><text x="0" y="15">x</text></svg>');
    $formula = '<img class="Wirisformula" style="max-width: none; vertical-align: -4px;" '
        ."src=\"data:image/svg+xml;base64,{$svg}\" "
        .'data-mathml="&#171;math&#187;&#171;mo&#187;x&#171;/mo&#187;&#171;/math&#187;" alt="x" role="math" width="13" height="19" align="middle">';

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set(array_merge(baseSoalPayload(), ['pertanyaan' => "<p>Jika x = {$formula}, berapa hasil 2x?</p>"]))
        ->call('save')
        ->assertHasNoErrors();

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal->pertanyaan)->toContain('class="Wirisformula"')
        ->and($soal->pertanyaan)->toContain('data-mathml=')
        ->and($soal->pertanyaan)->toContain('role="math"')
        ->and($soal->pertanyaan)->toContain('src="data:image/svg+xml;base64,')
        ->and($soal->pertanyaan)->toContain('vertical-align');
});

test('it rejects a data: image/svg+xml payload that embeds a script tag', function () {
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    $evilSvg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.cookie)</script></svg>');

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])
        ->set(array_merge(baseSoalPayload(), [
            'pertanyaan' => '<p>Soal <img src="data:image/svg+xml;base64,'.$evilSvg.'" alt="evil"></p>',
        ]))
        ->call('save')
        ->assertHasNoErrors();

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->first();
    expect($soal->pertanyaan)->not->toContain('<img')
        ->and($soal->pertanyaan)->not->toContain('<script');
});

test('an operator cannot open the soal form', function () {
    actingAsAdminRole('operator');
    $bankSoal = BankSoal::factory()->create();

    Livewire::test('admin.soal.form', ['bankSoalId' => $bankSoal->id])->assertForbidden();
});
