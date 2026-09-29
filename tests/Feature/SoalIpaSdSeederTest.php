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

function bankSoalIpaSd(): BankSoal
{
    return BankSoal::factory()->create([
        'jenjang_id' => Jenjang::factory()->create(['nama' => 'SD'])->id,
        'pelajaran_id' => Pelajaran::factory()->create(['nama' => 'IPA'])->id,
    ]);
}

test('it seeds 50 illustrated soal into the IPA SD bank soal, each with its own gambar', function () {
    $bankSoal = bankSoalIpaSd();

    $this->seed(SoalIpaSdSeeder::class);

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

test('re-running replaces its own soal but keeps soal written by hand', function () {
    $bankSoal = bankSoalIpaSd();
    Soal::factory()->create(['bank_soal_id' => $bankSoal->id, 'pertanyaan' => '<p>Soal buatan admin</p>']);

    $this->seed(SoalIpaSdSeeder::class);
    $this->seed(SoalIpaSdSeeder::class);

    expect(Soal::where('bank_soal_id', $bankSoal->id)->count())->toBe(51)
        ->and(Soal::where('pertanyaan', '<p>Soal buatan admin</p>')->exists())->toBeTrue();
});

test('it does nothing without an IPA SD bank soal', function () {
    BankSoal::factory()->create([
        'jenjang_id' => Jenjang::factory()->create(['nama' => 'SLTP'])->id,
        'pelajaran_id' => Pelajaran::factory()->create(['nama' => 'IPA'])->id,
    ]);

    $this->seed(SoalIpaSdSeeder::class);

    expect(Soal::count())->toBe(0);
});
