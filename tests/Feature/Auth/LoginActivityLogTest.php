<?php

use App\Models\Peserta;
use App\Models\User;
use App\Support\LogAktivitas;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new PermissionSeeder)->run();
    Role::findOrCreate('administrator', 'web');
});

function logAuth(string $event): Activity
{
    return Activity::query()->where('log_name', LogAktivitas::AUTH)->where('event', $event)->sole();
}

test('a peserta login is logged against the peserta, with guard, IP and device', function () {
    $peserta = Peserta::factory()->create(['noreg' => '1000001']);

    $this->withHeader('User-Agent', 'Browser Lab 1')
        ->post('/login', ['username' => '1000001', 'password' => 'password'])
        ->assertRedirect(route('cbt.dashboard'));

    $log = logAuth('login');

    expect($log->causer->is($peserta))->toBeTrue()
        ->and($log->properties['guard'])->toBe('peserta')
        ->and($log->properties['metode'])->toBe('password')
        ->and($log->properties['ip'])->toBe('127.0.0.1')
        ->and($log->properties['user_agent'])->toBe('Browser Lab 1');
});

test('the login itself does not show up as a peserta data change', function () {
    Peserta::factory()->create(['noreg' => '1000001']);

    // `remember` makes the guard write a new remember_token onto the row.
    $this->post('/login', ['username' => '1000001', 'password' => 'password', 'remember' => '1']);

    expect(Activity::query()->where('log_name', LogAktivitas::ADMIN)->where('event', 'updated')->exists())->toBeFalse();
});

test('a wrong peserta password is logged as a failed login without the password', function () {
    $peserta = Peserta::factory()->create(['noreg' => '1000001']);

    $this->post('/login', ['username' => '1000001', 'password' => 'tebakan-salah'])
        ->assertSessionHasErrors(['username' => 'No. Registrasi atau password salah.']);

    $log = logAuth('login_gagal');

    expect($log->causer->is($peserta))->toBeTrue()
        ->and($log->properties['username'])->toBe('1000001')
        ->and($log->properties['alasan'])->toBe('password_salah')
        ->and(json_encode($log->properties))->not->toContain('tebakan-salah');
    $this->assertGuest('peserta');
});

test('a login for an unknown noreg is logged anonymously', function () {
    $this->post('/login', ['username' => '9999999', 'password' => 'password']);

    $log = logAuth('login_gagal');

    expect($log->causer_id)->toBeNull()
        ->and($log->properties['username'])->toBe('9999999')
        ->and($log->properties['alasan'])->toBe('akun_tidak_ditemukan');
});

test('an admin-tier login is logged on the web guard', function () {
    $admin = User::factory()->create(['username' => 'budi']);
    $admin->assignRole('operator');

    $this->post('/admin/login', ['username' => 'budi', 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));

    $log = logAuth('login');

    expect($log->causer->is($admin))->toBeTrue()
        ->and($log->properties['guard'])->toBe('web');
});

test('an account without an admin-tier role is refused without ever being logged in', function () {
    User::factory()->create(['username' => 'tanpa.role']);

    $this->post('/admin/login', ['username' => 'tanpa.role', 'password' => 'password'])
        ->assertSessionHasErrors(['username' => 'Akun ini tidak memiliki akses ke halaman tersebut.']);

    $this->assertGuest('web');
    expect(Activity::query()->whereIn('event', ['login', 'logout'])->exists())->toBeFalse()
        ->and(logAuth('login_gagal')->properties['alasan'])->toBe('tidak_punya_akses');
});

test('a wrong admin password still gets the generic error', function () {
    $admin = User::factory()->create(['username' => 'budi']);
    $admin->assignRole('administrator');

    $this->post('/admin/login', ['username' => 'budi', 'password' => 'salah'])
        ->assertSessionHasErrors(['username' => 'Username atau password salah.']);

    expect(logAuth('login_gagal')->properties['alasan'])->toBe('password_salah');
});

test('logout is logged for whichever guard was logged in', function () {
    $peserta = actingAsPeserta();

    $this->post(route('logout'))->assertRedirect(route('login'));

    $log = logAuth('logout');

    expect($log->causer->is($peserta))->toBeTrue()
        ->and($log->properties['guard'])->toBe('peserta');
});

test('a Google login is logged with its method', function () {
    $admin = User::factory()->create(['email' => 'budi@sekolah.test']);
    $admin->assignRole('administrator');

    Socialite::fake('google', SocialiteUser::fake(['email' => 'budi@sekolah.test']));

    $this->get(route('admin.login.google.callback'));

    expect(logAuth('login')->properties['metode'])->toBe('google');
});

test('a Google account linked to no admin is logged as a failed login', function () {
    Socialite::fake('google', SocialiteUser::fake(['email' => 'asing@luar.test']));

    $this->get(route('admin.login.google.callback'));

    $log = logAuth('login_gagal');

    expect($log->properties['metode'])->toBe('google')
        ->and($log->properties['username'])->toBe('asing@luar.test')
        ->and($log->properties['alasan'])->toBe('akun_tidak_ditemukan');
});

test('five wrong passwords lock that account out for this IP, even with the right password', function () {
    Peserta::factory()->create(['noreg' => '1000001']);

    foreach (range(1, 5) as $percobaan) {
        $this->post('/login', ['username' => '1000001', 'password' => 'salah']);
    }

    $this->post('/login', ['username' => '1000001', 'password' => 'password'])
        ->assertSessionHasErrors('username');

    $this->assertGuest('peserta');
    expect(session('errors')->first('username'))->toContain('Terlalu banyak percobaan login')
        ->and(logAuth('login_diblokir')->properties['username'])->toBe('1000001');
});

test('one peserta being locked out does not block another peserta on the same IP', function () {
    Peserta::factory()->create(['noreg' => '1000001']);
    $lain = Peserta::factory()->create(['noreg' => '1000002']);

    foreach (range(1, 6) as $percobaan) {
        $this->post('/login', ['username' => '1000001', 'password' => 'salah']);
    }

    $this->post('/login', ['username' => '1000002', 'password' => 'password'])
        ->assertRedirect(route('cbt.dashboard'));

    $this->assertAuthenticatedAs($lain, 'peserta');
});

test('a whole lab logging in at once from one IP is not throttled', function () {
    $peserta = Peserta::factory()->count(30)->create();

    foreach ($peserta as $satu) {
        $this->post('/login', ['username' => $satu->noreg, 'password' => 'password'])
            ->assertRedirect(route('cbt.dashboard'));

        $this->post(route('logout'));
    }

    expect(Activity::query()->where('event', 'login')->count())->toBe(30);
});
