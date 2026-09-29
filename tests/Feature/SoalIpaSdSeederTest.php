<?php

use App\Models\BankSoal;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\Soal;
use Database\Seeders\SoalIpaSdSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function bankSoalDengan(string $jenjang, string $pelajaran): BankSoal
{
    return BankSoal::factory()->create([
        'jenjang_id' => Jenjang::factory()->create(['nama' => $jenjang])->id,
        'pelajaran_id' => Pelajaran::factory()->create(['nama' => $pelajaran])->id,
    ]);
}

function labelBankSoal(BankSoal $b): string
{
    return "{$b->id} — {$b->nama} ({$b->jenjang->nama}, {$b->pelajaran->nama})";
}

/**
 * Runs the seeder the way `db:seed --class=...` would, without the prompt.
 */
function seedIpaSdKe(int $bankSoalId): void
{
    app(SoalIpaSdSeeder::class)->setContainer(app())->__invoke(['bankSoalId' => $bankSoalId]);
}

test('it seeds 50 illustrated soal into the given bank soal, each with its own gambar', function () {
    $bankSoal = bankSoalDengan('SD', 'IPA');

    seedIpaSdKe($bankSoal->id);

    $soal = Soal::where('bank_soal_id', $bankSoal->id)->get();
    $gambar = Storage::disk('public')->files("soal/{$bankSoal->id}");

    expect($soal)->toHaveCount(50)
        ->and($gambar)->toHaveCount(50)
        ->and(collect($gambar)->every(fn ($path) => str_starts_with(Storage::disk('public')->get($path), '<svg')))->toBeTrue();

    foreach ($soal as $s) {
        expect($s->pertanyaan)->toMatch('#<img src="/storage/soal/'.$bankSoal->id.'/seed-ipa-sd-\d{2}\.svg"#')
            ->and($s->pilihan())->toHaveCount(4)
            ->and($s->pilihan())->toHaveKey($s->jawaban);
    }

    // Correct answers are spread over A–D, not all on one letter.
    expect($soal->countBy('jawaban')->keys()->sort()->values()->all())->toBe(['A', 'B', 'C', 'D']);
});

test('db:seed asks which bank soal to fill', function () {
    $lain = bankSoalDengan('SLTA', 'Matematika');
    $ipaSd = bankSoalDengan('SD', 'IPA');

    $this->artisan('db:seed', ['--class' => SoalIpaSdSeeder::class])
        ->expectsQuestion('Pilih bank soal', labelBankSoal($ipaSd))
        ->assertSuccessful();

    expect(Soal::where('bank_soal_id', $ipaSd->id)->count())->toBe(50)
        ->and(Soal::where('bank_soal_id', $lain->id)->count())->toBe(0);
});

test('picking a non IPA SD bank soal needs confirmation, and declining adds nothing', function (bool $lanjut, int $jumlah) {
    $matematika = bankSoalDengan('SLTA', 'Matematika');

    $this->artisan('db:seed', ['--class' => SoalIpaSdSeeder::class])
        ->expectsQuestion('Pilih bank soal', labelBankSoal($matematika))
        ->expectsConfirmation('Tetap lanjut?', $lanjut ? 'yes' : 'no')
        ->assertSuccessful();

    expect(Soal::where('bank_soal_id', $matematika->id)->count())->toBe($jumlah);
})->with([
    'lanjut' => [true, 50],
    'batal' => [false, 0],
]);

test('an unknown bank soal id adds nothing', function () {
    seedIpaSdKe(999);

    expect(Soal::count())->toBe(0);
});

test('re-running replaces its own soal but keeps soal written by hand', function () {
    $bankSoal = bankSoalDengan('SD', 'IPA');
    Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => '<p>Soal buatan admin</p>']);

    seedIpaSdKe($bankSoal->id);
    seedIpaSdKe($bankSoal->id);

    expect(Soal::where('bank_soal_id', $bankSoal->id)->count())->toBe(51)
        ->and(Soal::where('pertanyaan', '<p>Soal buatan admin</p>')->exists())->toBeTrue();
});
