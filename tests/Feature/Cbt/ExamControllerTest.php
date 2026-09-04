<?php

use App\Models\BankSoal;
use App\Models\SiswaSoal;
use App\Models\SiswaUjian;
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

test('dashboard only lists the logged-in siswa own ujian', function () {
    $siswa = actingAsSiswa();
    $ujianSaya = ujianDenganSoal(2);
    SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujianSaya->id]);

    $ujianOrangLain = ujianDenganSoal(2);
    SiswaUjian::factory()->create(['ujian_id' => $ujianOrangLain->id]);

    $this->get(route('cbt.dashboard'))
        ->assertOk()
        ->assertSee($ujianSaya->nama)
        ->assertDontSee($ujianOrangLain->nama);
});

test('mulai starts the attempt and redirects to kerjakan', function () {
    $siswa = actingAsSiswa();
    $ujian = ujianDenganSoal(3);
    $siswaUjian = SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);

    $this->post(route('cbt.ujian.mulai', $siswaUjian))
        ->assertRedirect(route('cbt.ujian.kerjakan', $siswaUjian));

    expect($siswaUjian->fresh()->waktu_mulai)->not->toBeNull()
        ->and($siswaUjian->siswaSoal()->count())->toBe(3);
});

test('mulai is blocked outside the exam session window', function () {
    $siswa = actingAsSiswa();
    $ujian = ujianDenganSoal(2, ['sesi_mulai' => now()->addDay(), 'sesi_selesai' => now()->addDay()->addHours(2)]);
    $siswaUjian = SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);

    $this->post(route('cbt.ujian.mulai', $siswaUjian))->assertRedirect();

    expect($siswaUjian->fresh()->waktu_mulai)->toBeNull();
});

test('mulai redirects to hasil when the attempt is already submitted', function () {
    $siswa = actingAsSiswa();
    $ujian = ujianDenganSoal(2);
    $siswaUjian = SiswaUjian::factory()->selesai()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);

    $this->post(route('cbt.ujian.mulai', $siswaUjian))
        ->assertRedirect(route('cbt.ujian.hasil', $siswaUjian));
});

test('a siswa cannot mulai, kerjakan, or lihat hasil of another siswa attempt', function () {
    actingAsSiswa();
    $ujian = ujianDenganSoal(2);
    $siswaUjianOrangLain = SiswaUjian::factory()->create(['ujian_id' => $ujian->id]);

    $this->post(route('cbt.ujian.mulai', $siswaUjianOrangLain))->assertForbidden();
    $this->get(route('cbt.ujian.kerjakan', $siswaUjianOrangLain))->assertForbidden();
    $this->get(route('cbt.ujian.hasil', $siswaUjianOrangLain))->assertForbidden();
});

test('kerjakan redirects to dashboard when the attempt has not started', function () {
    $siswa = actingAsSiswa();
    $ujian = ujianDenganSoal(2);
    $siswaUjian = SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);

    $this->get(route('cbt.ujian.kerjakan', $siswaUjian))
        ->assertRedirect(route('cbt.dashboard'));
});

test('kerjakan redirects to hasil once submitted', function () {
    $siswa = actingAsSiswa();
    $ujian = ujianDenganSoal(2);
    $siswaUjian = SiswaUjian::factory()->selesai()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);

    $this->get(route('cbt.ujian.kerjakan', $siswaUjian))
        ->assertRedirect(route('cbt.ujian.hasil', $siswaUjian));
});

test('kerjakan shows the question text and pilihan but never the correct jawaban', function () {
    $siswa = actingAsSiswa();
    $ujian = ujianDenganSoal(1);
    $soal = Soal::first();
    $soal->update(['pertanyaan' => 'Pertanyaan unik XYZ', 'pilih_a' => 'Opsi Rahasia A', 'jawaban' => 'A']);
    $siswaUjian = SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id]);
    (new ExamAttemptService)->mulai($siswaUjian);

    $response = $this->get(route('cbt.ujian.kerjakan', $siswaUjian))->assertOk();

    $response->assertSee('Pertanyaan unik XYZ');
    $response->assertSee('Opsi Rahasia A');
    // The raw `jawaban` column value must never leak into the page payload.
    expect($response->getContent())->not->toContain('"jawaban":"A"');
});

test('kerjakan auto-finalizes and redirects to hasil once the deadline has passed', function () {
    $siswa = actingAsSiswa();
    $ujian = ujianDenganSoal(2, ['durasi_detik' => 60]);
    $siswaUjian = SiswaUjian::factory()->create([
        'siswa_id' => $siswa->id,
        'ujian_id' => $ujian->id,
        'waktu_mulai' => now()->subMinutes(5),
    ]);
    foreach (Soal::all() as $i => $soal) {
        SiswaSoal::factory()->create(['siswa_ujian_id' => $siswaUjian->id, 'soal_id' => $soal->id, 'urutan' => $i + 1]);
    }

    $this->get(route('cbt.ujian.kerjakan', $siswaUjian))
        ->assertRedirect(route('cbt.ujian.hasil', $siswaUjian));

    expect($siswaUjian->fresh()->waktu_selesai)->not->toBeNull();
});

test('hasil redirects back to kerjakan when the attempt is not yet submitted', function () {
    $siswa = actingAsSiswa();
    $ujian = ujianDenganSoal(2);
    $siswaUjian = SiswaUjian::factory()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id, 'waktu_mulai' => now()]);

    $this->get(route('cbt.ujian.hasil', $siswaUjian))
        ->assertRedirect(route('cbt.ujian.kerjakan', $siswaUjian));
});

test('hasil shows the score once submitted', function () {
    $siswa = actingAsSiswa();
    $ujian = ujianDenganSoal(2);
    $siswaUjian = SiswaUjian::factory()->selesai()->create(['siswa_id' => $siswa->id, 'ujian_id' => $ujian->id, 'nilai' => 27]);

    $this->get(route('cbt.ujian.hasil', $siswaUjian))
        ->assertOk()
        ->assertSee('27');
});
