<?php

use App\Exports\LogAktivitasExport;
use App\Models\Jenjang;
use App\Models\Peserta;
use App\Models\PesertaUjian;
use App\Models\Ruangan;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\User;
use App\Support\LogAktivitas;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new PermissionSeeder)->run();
});

function logAdminTerakhir(): Activity
{
    return Activity::query()->where('log_name', LogAktivitas::ADMIN)->latest('id')->firstOrFail();
}

// --- Akses -----------------------------------------------------------------

test('administrator can open Log Aktivitas without being granted anything', function () {
    actingAsAdmin();

    $this->get(route('admin.log-aktivitas.index'))
        ->assertOk()
        ->assertSee('Deteksi Login Ganda');
});

test('admin and operator are refused Log Aktivitas by default', function () {
    actingAsAdminRole('admin');
    $this->get(route('admin.log-aktivitas.index'))->assertForbidden();
    $this->get(route('admin.log-aktivitas.export'))->assertForbidden();

    actingAsAdminRole('operator');
    $this->get(route('admin.log-aktivitas.index'))->assertForbidden();
});

test('granting log-aktivitas.view on Kelola Peran opens the page to that role, and the grant itself is logged', function () {
    actingAsAdmin();

    Livewire::test('admin.roles.manager')
        ->set('checked.operator.log-aktivitas__view', true)
        ->call('save');

    expect(Role::findByName('operator', 'web')->hasPermissionTo('log-aktivitas.view'))->toBeTrue();

    $log = logAdminTerakhir();
    expect($log->event)->toBe('ubah_peran')
        ->and($log->properties['diberikan'])->toBe(['log-aktivitas.view'])
        ->and($log->properties['dicabut'])->toBe([]);

    // Not actingAsAdminRole(): it re-runs PermissionSeeder, which would
    // reset operator back to its default permissions.
    $operator = User::factory()->create();
    $operator->assignRole('operator');

    $this->actingAs($operator)->get(route('admin.log-aktivitas.index'))->assertOk();
});

test('the sidebar shows Log Aktivitas only to who may open it', function () {
    actingAsAdmin();
    $this->get(route('admin.dashboard'))->assertSee('Log Aktivitas');

    actingAsAdminRole('operator');
    $this->get(route('admin.dashboard'))->assertDontSee('Log Aktivitas');
});

test('the components refuse a user without the permission even when mounted directly', function () {
    actingAsAdminRole('operator');

    Livewire::test('admin.log-aktivitas.manager')->assertForbidden();
    Livewire::test('admin.log-aktivitas.deteksi')->assertForbidden();
});

// --- Perubahan data oleh admin ----------------------------------------------

test('creating, changing and deleting data is logged with who did it and what changed', function () {
    $admin = actingAsAdmin();

    $jenjang = Jenjang::factory()->create(['kode' => 'SD', 'nama' => 'Sekolah Dasar']);
    $jenjang->update(['nama' => 'SD Sederajat']);

    $log = logAdminTerakhir();
    expect($log->description)->toBe('Jenjang diubah')
        ->and($log->causer->is($admin))->toBeTrue()
        ->and($log->properties['old'])->toBe(['nama' => 'Sekolah Dasar'])
        ->and($log->properties['attributes'])->toBe(['nama' => 'SD Sederajat'])
        ->and($log->properties['label'])->toBe('SD Sederajat');

    $jenjang->delete();

    expect(logAdminTerakhir()->description)->toBe('Jenjang dihapus')
        ->and(logAdminTerakhir()->properties['label'])->toBe('SD Sederajat');
});

test('a peserta password change is logged, but never its value or password_plain', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create();

    Livewire::test('admin.peserta.manager')->call('resetPassword', $peserta->id);

    $log = logAdminTerakhir();
    $baru = $peserta->fresh();

    expect($log->description)->toBe('Peserta diubah')
        ->and($log->properties['attributes'])->toBe(['password' => '••••••'])
        ->and(json_encode($log->properties))->not->toContain($baru->password_plain)
        ->not->toContain($baru->password);
});

test('a soal edit is logged without its content', function () {
    actingAsAdmin();
    $soal = Soal::factory()->create(['pertanyaan' => '<p>Ibu kota Indonesia?</p>']);

    $soal->update(['pertanyaan' => '<p>Ibu kota Jawa Barat?</p>', 'jawaban' => 'B']);

    $log = logAdminTerakhir();
    expect($log->description)->toBe('Soal diubah')
        ->and(json_encode($log->properties))->not->toContain('Ibu kota')
        ->not->toContain('jawaban');
});

test('changing a user role is logged, since roles never touch the users row', function () {
    actingAsAdmin();
    $user = User::factory()->create(['name' => 'Budi']);
    $user->assignRole('operator');

    Livewire::test('admin.users.manager')
        ->call('edit', $user->id)
        ->set('role', 'admin')
        ->call('save')
        ->assertHasNoErrors();

    $log = logAdminTerakhir();
    expect($log->event)->toBe('ubah_role')
        ->and($log->properties['old'])->toBe(['role' => 'operator'])
        ->and($log->properties['attributes'])->toBe(['role' => 'admin']);
});

test('an import is logged as one summary entry, not one per row', function () {
    Storage::fake('local');
    actingAsAdmin();
    Jenjang::factory()->create(['nama' => 'SD']);

    $csv = "noreg,nama,jenjang,asal_sekolah,password\n"
        ."1000001,Budi,SD,SD Contoh,\n"
        ."1000002,Citra,SD,SD Contoh,\n";

    Livewire::test('admin.peserta.import')
        ->set('file', UploadedFile::fake()->createWithContent('peserta.csv', $csv))
        ->call('import')
        ->assertSet('imported', 2);

    expect(Activity::query()->where('subject_type', (new Peserta)->getMorphClass())->exists())->toBeFalse();

    $log = logAdminTerakhir();
    expect($log->event)->toBe('import_peserta')
        ->and($log->properties['jumlah'])->toBe(2)
        ->and($log->properties['file'])->toBe('peserta.csv');
});

test('moving many peserta between ruangan at once is logged, though it bypasses model events', function () {
    actingAsAdmin();
    $ujian = Ujian::factory()->create(['nama' => 'OSN IPA SD']);
    $ruangan = Ruangan::factory()->create(['nama' => 'Lab 1']);
    $terdaftar = PesertaUjian::factory()->count(2)->create(['ujian_id' => $ujian->id]);

    Livewire::test('admin.peserta-ujian.manager', ['ujianId' => $ujian->id])
        ->set('selectedIds', $terdaftar->pluck('peserta_id')->all())
        ->set('targetRuangan', (string) $ruangan->id)
        ->call('pindahkanTerpilih');

    $log = logAdminTerakhir();
    expect($log->event)->toBe('pindah_ruangan')
        ->and($log->description)->toBe('2 peserta dipindahkan ke Lab 1 — OSN IPA SD')
        ->and($log->subject->is($ujian))->toBeTrue();
});

// --- Halaman ---------------------------------------------------------------

test('the list shows only the chosen log, narrowed by its filters', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['nama' => 'Budi Santoso']);
    LogAktivitas::catat(LogAktivitas::AUTH, 'login', 'Login berhasil', $peserta, properti: ['guard' => 'peserta']);
    LogAktivitas::catat(LogAktivitas::AUTH, 'login_gagal', 'Login gagal', properti: ['guard' => 'web', 'username' => 'penyusup']);
    LogAktivitas::admin('reset_data', 'Reset data dijalankan');

    Livewire::test('admin.log-aktivitas.manager')
        ->assertSee('Budi Santoso')
        ->assertSee('penyusup')
        ->assertDontSee('Reset data dijalankan')
        ->set('event', 'login_gagal')
        ->assertDontSee('Budi Santoso')
        ->assertSee('penyusup')
        ->call('pilihLog', LogAktivitas::ADMIN)
        ->assertSee('Reset data dijalankan')
        ->assertDontSee('penyusup');
});

test('the date range excludes entries outside it', function () {
    actingAsAdmin();
    $this->travelTo(now()->subDays(10));
    LogAktivitas::admin('reset_data', 'Reset lama sekali');
    $this->travelBack();
    LogAktivitas::admin('reset_data', 'Reset hari ini');

    Livewire::test('admin.log-aktivitas.manager')
        ->call('pilihLog', LogAktivitas::ADMIN)
        ->assertSee('Reset hari ini')
        ->assertDontSee('Reset lama sekali');
});

test('a deleted peserta is still named in its old log entries', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['nama' => 'Citra Lestari']);
    LogAktivitas::catat(LogAktivitas::AUTH, 'login', 'Login berhasil', $peserta, properti: ['guard' => 'peserta']);

    $peserta->delete();

    Livewire::test('admin.log-aktivitas.manager')->assertSee('Citra Lestari');
});

test('the detail modal shows old and new values', function () {
    actingAsAdmin();
    $ruangan = Ruangan::factory()->create(['nama' => 'Lab Lama']);
    $ruangan->update(['nama' => 'Lab Baru']);

    Livewire::test('admin.log-aktivitas.manager')
        ->call('pilihLog', LogAktivitas::ADMIN)
        ->call('lihat', logAdminTerakhir()->id)
        ->assertSet('showDetail', true)
        ->assertSee('Lab Lama')
        ->assertSee('Lab Baru');
});

test('export downloads the same filtered rows', function () {
    Excel::fake();
    actingAsAdmin();

    $this->get(route('admin.log-aktivitas.export', ['log' => 'admin', 'event' => 'reset_data', 'dari' => '2026-10-01', 'sampai' => '2026-10-21']))
        ->assertOk();

    Excel::assertDownloaded('Log-Aktivitas-Admin-2026-10-01-2026-10-21.xlsx', function (LogAktivitasExport $export) {
        $sql = $export->query()->toRawSql();

        return str_contains($sql, "'admin'") && str_contains($sql, "'reset_data'");
    });
});

test('unknown filter values from the query string are ignored', function () {
    expect(LogAktivitas::filter(['log' => 'rahasia', 'event' => 'drop table', 'dari' => 'kemarin']))
        ->toBe(['log' => 'auth', 'dari' => null, 'sampai' => null, 'event' => null, 'kataKunci' => null]);
});

// --- Login Terakhir --------------------------------------------------------

test('Kelola Pengguna and Kelola Peserta show each account\'s last login', function () {
    actingAsAdmin();
    $peserta = Peserta::factory()->create(['nama' => 'Budi Santoso']);
    Peserta::factory()->create(['nama' => 'Belum Login']);

    $this->travelTo(now()->subHours(3));
    LogAktivitas::catat(LogAktivitas::AUTH, 'login', 'Login berhasil', $peserta, properti: ['guard' => 'peserta']);
    $this->travelBack();

    $daftar = Peserta::query()->withLoginTerakhir()->orderBy('id')->get();

    expect($daftar[0]->login_terakhir->diffInHours(now()))->toEqualWithDelta(3, 0.1)
        ->and($daftar[1]->login_terakhir)->toBeNull();

    Livewire::test('admin.peserta.manager')
        ->assertSee('3 jam yang lalu')
        ->assertSee('Belum pernah');

    Livewire::test('admin.users.manager')->assertSee('Login Terakhir');
});

// --- Hapus Log Lama ---------------------------------------------------------

test('administrator can delete log entries past the retention, keeping recent ones', function () {
    actingAsAdmin();
    $this->travelTo(now()->subDays(91));
    LogAktivitas::admin('reset_data', 'Reset kuno');
    $this->travelBack();
    LogAktivitas::admin('reset_data', 'Reset baru');

    Livewire::test('admin.log-aktivitas.manager')
        ->assertSee('Ada 1 log yang lebih lama dari 90 hari')
        ->call('hapusLogLama')
        ->assertSet('statusMessage', '1 log lama dihapus.');

    expect(Activity::query()->where('description', 'Reset kuno')->exists())->toBeFalse()
        ->and(Activity::query()->where('description', 'Reset baru')->exists())->toBeTrue()
        ->and(logAdminTerakhir()->event)->toBe('hapus_log');
});

test('a role merely granted log-aktivitas.view cannot delete old logs', function () {
    Role::findByName('operator', 'web')->givePermissionTo('log-aktivitas.view');
    $operator = User::factory()->create();
    $operator->assignRole('operator');
    $this->actingAs($operator);

    $this->travelTo(now()->subDays(91));
    LogAktivitas::admin('reset_data', 'Reset kuno');
    $this->travelBack();

    Livewire::test('admin.log-aktivitas.manager')
        ->assertDontSee('Hapus Log Lama')
        ->call('hapusLogLama')
        ->assertForbidden();

    expect(Activity::query()->where('description', 'Reset kuno')->exists())->toBeTrue();
});
