<?php

use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it lists existing peserta with their jenjang', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create(['nama' => 'Sekolah Dasar']);
    Peserta::factory()->create(['jenjang_id' => $jenjang->id, 'nama' => 'Budi', 'noreg' => '1000000001']);

    Livewire::test('admin.peserta.manager')
        ->assertSee('Budi')
        ->assertSee('1000000001')
        ->assertSee('Sekolah Dasar');
});

test('it validates required fields before creating', function () {
    actingAsAdmin();

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->call('save')
        ->assertHasErrors(['noreg' => 'required', 'nama' => 'required', 'jenjangId' => 'required', 'asalSekolah' => 'required'])
        ->assertHasNoErrors('password');

    expect(Peserta::count())->toBe(0);
});

test('creating a peserta with the password left blank generates one', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();

    $component = Livewire::test('admin.peserta.manager')
        ->call('create')
        ->set('noreg', '1000000001')
        ->set('nama', 'Budi Santoso')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Contoh')
        ->call('save')
        ->assertHasNoErrors();

    $peserta = Peserta::where('noreg', '1000000001')->first();
    expect($peserta->password_plain)->toMatch(PASSWORD_PESERTA_PATTERN)
        ->and(Hash::check($peserta->password_plain, $peserta->password))->toBeTrue();
    $component->assertSet('statusMessage', "Peserta ditambahkan. Password: {$peserta->password_plain}");
});

test('the acak button fills the password field with a generated password', function () {
    actingAsAdmin();

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->call('acakPassword')
        ->assertSet('password', fn (string $password) => preg_match(PASSWORD_PESERTA_PATTERN, $password) === 1);
});

test('it creates a peserta with a working login', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->set('noreg', '1000000001')
        ->set('nama', 'Budi Santoso')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Contoh')
        ->set('password', 'rahasia')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $peserta = Peserta::where('noreg', '1000000001')->first();
    expect($peserta)->not->toBeNull()
        ->and(Hash::check('rahasia', $peserta->password))->toBeTrue();
});

test('it records minat lomba (checked pelajaran) when creating a peserta', function () {
    actingAsAdmin();
    $jenjang = Jenjang::factory()->create();
    $matematika = Pelajaran::factory()->create(['nama' => 'Matematika']);
    $ipa = Pelajaran::factory()->create(['nama' => 'IPA']);

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->set('noreg', '1000000001')
        ->set('nama', 'Budi Santoso')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Contoh')
        ->set('password', 'rahasia')
        ->set('pelajaranLombaIds', [$matematika->id])
        ->call('save')
        ->assertHasNoErrors();

    $peserta = Peserta::where('noreg', '1000000001')->first();
    expect($peserta->pelajaranLomba->pluck('id')->all())->toBe([$matematika->id])
        ->and($peserta->pelajaranLomba->pluck('id'))->not->toContain($ipa->id);
});

test('editing a peserta prefills their minat lomba and syncs changes (including unchecking)', function () {
    actingAsAdmin();
    $matematika = Pelajaran::factory()->create(['nama' => 'Matematika']);
    $ipa = Pelajaran::factory()->create(['nama' => 'IPA']);
    $peserta = Peserta::factory()->create();
    $peserta->pelajaranLomba()->attach([$matematika->id, $ipa->id]);

    Livewire::test('admin.peserta.manager')
        ->call('edit', $peserta->id)
        ->assertSet('pelajaranLombaIds', fn ($ids) => in_array($matematika->id, $ids) && in_array($ipa->id, $ids))
        ->set('pelajaranLombaIds', [$ipa->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($peserta->fresh()->pelajaranLomba->pluck('id')->all())->toBe([$ipa->id]);
});

test('it rejects a duplicate noreg', function () {
    actingAsAdmin();
    Peserta::factory()->create(['noreg' => '1000000001']);
    $jenjang = Jenjang::factory()->create();

    Livewire::test('admin.peserta.manager')
        ->call('create')
        ->set('noreg', '1000000001')
        ->set('nama', 'Lain')
        ->set('jenjangId', $jenjang->id)
        ->set('asalSekolah', 'SD Lain')
        ->set('password', 'rahasia')
        ->call('save')
        ->assertHasErrors(['noreg']);

    expect(Peserta::count())->toBe(1);
});

test('it prefills and updates an existing peserta without requiring a new password', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['nama' => 'Lama']);

    Livewire::test('admin.peserta.manager')
        ->call('edit', $peserta->id)
        ->assertSet('nama', 'Lama')
        ->assertSet('password', '')
        ->set('nama', 'Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($peserta->fresh()->nama)->toBe('Baru');
});

test('it updates the noreg used to log in', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['noreg' => '1000000001']);

    Livewire::test('admin.peserta.manager')
        ->call('edit', $peserta->id)
        ->set('noreg', '2000000002')
        ->call('save')
        ->assertHasNoErrors();

    expect($peserta->fresh()->noreg)->toBe('2000000002');
});

test('resetting a peserta password generates a new one instead of using their noreg', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['noreg' => '1000000001', 'password' => Hash::make('rahasia-lama')]);

    Livewire::test('admin.peserta.manager')
        ->call('resetPassword', $peserta->id)
        ->assertSet('errorMessage', null);

    $peserta->refresh();
    expect($peserta->password_plain)->toMatch(PASSWORD_PESERTA_PATTERN)
        ->and(Hash::check($peserta->password_plain, $peserta->password))->toBeTrue()
        ->and(Hash::check('rahasia-lama', $peserta->password))->toBeFalse()
        ->and(Hash::check('1000000001', $peserta->password))->toBeFalse();
});

test('acak ulang password only regenerates passwords of the ticked peserta', function () {
    actingAsAdmin();
    [$dicentang, $lainDicentang, $tidakDicentang] = Peserta::factory()->count(3)->create()->all();

    Livewire::test('admin.peserta.manager')
        ->set('selectedIds', [(string) $dicentang->id, (string) $lainDicentang->id])
        ->call('acakUlangPasswordMassal')
        ->assertSet('statusMessage', 'Password 2 peserta berhasil diacak ulang. Klik Export Terpilih untuk mencetak daftarnya.')
        // Kept, so "Export Terpilih" prints exactly these peserta next.
        ->assertSet('selectedIds', [(string) $dicentang->id, (string) $lainDicentang->id])
        ->assertSeeHtml(e(route('admin.peserta.export', ['ids' => [(string) $dicentang->id, (string) $lainDicentang->id]])))
        ->assertSee('Export Terpilih (2)');

    foreach ([$dicentang, $lainDicentang] as $peserta) {
        $peserta->refresh();
        expect($peserta->password_plain)->toMatch(PASSWORD_PESERTA_PATTERN)
            ->and(Hash::check($peserta->password_plain, $peserta->password))->toBeTrue();
    }

    // The factory's default credentials, left untouched.
    expect($tidakDicentang->fresh()->password_plain)->toBe('password');
});

test('acak ulang password with nothing ticked changes no one', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create();

    Livewire::test('admin.peserta.manager')
        ->call('acakUlangPasswordMassal')
        ->assertSet('errorMessage', 'Centang dulu peserta yang password-nya mau diacak ulang.')
        ->assertSet('statusMessage', null);

    expect($peserta->fresh()->password_plain)->toBe('password');
});

test('the header checkbox ticks every peserta on the current page, then clears them', function () {
    actingAsAdmin();
    $halamanPertama = Peserta::factory()->count(11)->create()->sortBy('noreg')->take(10)->pluck('id')->all();

    Livewire::test('admin.peserta.manager')
        ->call('toggleSemuaDiHalaman')
        ->assertSet('selectedIds', fn (array $ids) => collect($ids)->sort()->values()->all() === collect($halamanPertama)->sort()->values()->all())
        ->call('toggleSemuaDiHalaman')
        ->assertSet('selectedIds', []);
});

test('ticked peserta are cleared when the page or the filter changes', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->count(11)->create();

    Livewire::test('admin.peserta.manager')
        ->set('selectedIds', [(string) $peserta->first()->id])
        ->call('nextPage')
        ->assertSet('selectedIds', [])
        ->set('selectedIds', [(string) $peserta->last()->id])
        ->set('search', 'Budi')
        ->assertSet('selectedIds', [])
        ->set('selectedIds', [(string) $peserta->last()->id])
        ->set('filterPelajaranId', (string) Pelajaran::factory()->create()->id)
        ->assertSet('selectedIds', []);
});

test('the pelajaran filter lists only peserta with that pelajaran, combinable with the jenjang filter', function () {
    actingAsAdmin();
    $slta = Jenjang::factory()->create();
    $matematika = Pelajaran::factory()->create(['nama' => 'Matematika']);
    $ipa = Pelajaran::factory()->create(['nama' => 'IPA']);

    $cocok = Peserta::factory()->create(['jenjang_id' => $slta->id, 'nama' => 'Cocok Semua']);
    $cocok->pelajaranLomba()->attach([$matematika->id, $ipa->id]);
    Peserta::factory()->create(['jenjang_id' => $slta->id, 'nama' => 'Pelajaran Lain'])->pelajaranLomba()->attach($ipa->id);
    Peserta::factory()->create(['nama' => 'Jenjang Lain'])->pelajaranLomba()->attach($matematika->id);
    Peserta::factory()->create(['jenjang_id' => $slta->id, 'nama' => 'Tanpa Pelajaran']);

    Livewire::test('admin.peserta.manager')
        ->set('filterJenjangId', (string) $slta->id)
        ->set('filterPelajaranId', (string) $matematika->id)
        ->assertSee('Cocok Semua')
        ->assertDontSee('Pelajaran Lain')
        ->assertDontSee('Jenjang Lain')
        ->assertDontSee('Tanpa Pelajaran')
        ->assertSeeHtml(e(route('admin.peserta.export', ['jenjang' => $slta->id, 'pelajaran' => $matematika->id])));
});

test('deleting a peserta removes their login account', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create();

    Livewire::test('admin.peserta.manager')->call('delete', $peserta->id);

    expect(Peserta::find($peserta->id))->toBeNull();
});

test('the list paginates with the app-styled pagination view', function () {
    actingAsAdmin();
    Peserta::factory()->count(11)->create();

    // 10 per page (see manager.php's ->paginate(10)), so an 11th row pushes
    // a "page 2" link through resources/views/vendor/pagination/tailwind.blade.php
    // (our styled override of Laravel's default pagination view).
    Livewire::test('admin.peserta.manager')
        ->assertSeeHtml('aria-current="page"')
        ->assertSeeHtml('aria-label="Go to page 2"');
});
