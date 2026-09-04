<?php

use App\Models\BankSoal;
use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Services\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ujianDenganSoal(int $jumlahSoal = 3, array $ujianOverrides = []): Ujian
{
    $bankSoal = BankSoal::factory()->create();
    Soal::factory()->count($jumlahSoal)->create(['bank_soal_id' => $bankSoal->id]);

    return Ujian::factory()->create(array_merge([
        'bank_soal_id' => $bankSoal->id,
        'jumlah_soal' => $jumlahSoal,
        'sesi_mulai' => now()->subMinute(),
        'sesi_selesai' => now()->addHour(),
    ], $ujianOverrides));
}

test('dashboard only lists the logged-in peserta own ujian', function () {
    $peserta = actingAsPeserta();
    $ujianSaya = ujianDenganSoal(2);
    PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujianSaya->id]);

    $ujianOrangLain = ujianDenganSoal(2);
    PesertaUjian::factory()->create(['ujian_id' => $ujianOrangLain->id]);

    $this->get(route('cbt.dashboard'))
        ->assertOk()
        ->assertSee($ujianSaya->nama)
        ->assertDontSee($ujianOrangLain->nama);
});

test('mulai starts the attempt and redirects to kerjakan', function () {
    $peserta = actingAsPeserta();
    $ujian = ujianDenganSoal(3);
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);

    $this->post(route('cbt.ujian.mulai', $pesertaUjian))
        ->assertRedirect(route('cbt.ujian.kerjakan', $pesertaUjian));

    expect($pesertaUjian->fresh()->waktu_mulai)->not->toBeNull()
        ->and($pesertaUjian->pesertaSoal()->count())->toBe(3);
});

test('mulai is blocked outside the exam session window', function () {
    $peserta = actingAsPeserta();
    $ujian = ujianDenganSoal(2, ['sesi_mulai' => now()->addDay(), 'sesi_selesai' => now()->addDay()->addHours(2)]);
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);

    $this->post(route('cbt.ujian.mulai', $pesertaUjian))->assertRedirect();

    expect($pesertaUjian->fresh()->waktu_mulai)->toBeNull();
});

test('mulai redirects to hasil when the attempt is already submitted', function () {
    $peserta = actingAsPeserta();
    $ujian = ujianDenganSoal(2);
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);

    $this->post(route('cbt.ujian.mulai', $pesertaUjian))
        ->assertRedirect(route('cbt.ujian.hasil', $pesertaUjian));
});

test('a peserta cannot mulai, kerjakan, or lihat hasil of another peserta attempt', function () {
    actingAsPeserta();
    $ujian = ujianDenganSoal(2);
    $pesertaUjianOrangLain = PesertaUjian::factory()->create(['ujian_id' => $ujian->id]);

    $this->post(route('cbt.ujian.mulai', $pesertaUjianOrangLain))->assertForbidden();
    $this->get(route('cbt.ujian.kerjakan', $pesertaUjianOrangLain))->assertForbidden();
    $this->get(route('cbt.ujian.hasil', $pesertaUjianOrangLain))->assertForbidden();
});

test('kerjakan redirects to dashboard when the attempt has not started', function () {
    $peserta = actingAsPeserta();
    $ujian = ujianDenganSoal(2);
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);

    $this->get(route('cbt.ujian.kerjakan', $pesertaUjian))
        ->assertRedirect(route('cbt.dashboard'));
});

test('kerjakan redirects to hasil once submitted', function () {
    $peserta = actingAsPeserta();
    $ujian = ujianDenganSoal(2);
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);

    $this->get(route('cbt.ujian.kerjakan', $pesertaUjian))
        ->assertRedirect(route('cbt.ujian.hasil', $pesertaUjian));
});

test('kerjakan shows the question text and pilihan but never the correct jawaban', function () {
    $peserta = actingAsPeserta();
    $ujian = ujianDenganSoal(1);
    $soal = Soal::first();
    $soal->update(['pertanyaan' => 'Pertanyaan unik XYZ', 'pilih_a' => 'Opsi Rahasia A', 'jawaban' => 'A']);
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id]);
    (new ExamAttemptService)->mulai($pesertaUjian);

    $response = $this->get(route('cbt.ujian.kerjakan', $pesertaUjian))->assertOk();

    $response->assertSee('Pertanyaan unik XYZ');
    $response->assertSee('Opsi Rahasia A');
    // The raw `jawaban` column value must never leak into the page payload.
    expect($response->getContent())->not->toContain('"jawaban":"A"');
});

test('kerjakan auto-finalizes and redirects to hasil once the deadline has passed', function () {
    $peserta = actingAsPeserta();
    $ujian = ujianDenganSoal(2, ['durasi_detik' => 60]);
    $pesertaUjian = PesertaUjian::factory()->create([
        'peserta_id' => $peserta->id,
        'ujian_id' => $ujian->id,
        'waktu_mulai' => now()->subMinutes(5),
    ]);
    foreach (Soal::all() as $i => $soal) {
        PesertaSoal::factory()->create(['peserta_ujian_id' => $pesertaUjian->id, 'soal_id' => $soal->id, 'urutan' => $i + 1]);
    }

    $this->get(route('cbt.ujian.kerjakan', $pesertaUjian))
        ->assertRedirect(route('cbt.ujian.hasil', $pesertaUjian));

    expect($pesertaUjian->fresh()->waktu_selesai)->not->toBeNull();
});

test('hasil redirects back to kerjakan when the attempt is not yet submitted', function () {
    $peserta = actingAsPeserta();
    $ujian = ujianDenganSoal(2);
    $pesertaUjian = PesertaUjian::factory()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id, 'waktu_mulai' => now()]);

    $this->get(route('cbt.ujian.hasil', $pesertaUjian))
        ->assertRedirect(route('cbt.ujian.kerjakan', $pesertaUjian));
});

test('hasil shows the score once submitted', function () {
    $peserta = actingAsPeserta();
    $ujian = ujianDenganSoal(2);
    $pesertaUjian = PesertaUjian::factory()->selesai()->create(['peserta_id' => $peserta->id, 'ujian_id' => $ujian->id, 'nilai' => 27]);

    $this->get(route('cbt.ujian.hasil', $pesertaUjian))
        ->assertOk()
        ->assertSee('27');
});
