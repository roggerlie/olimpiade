<?php

use App\Models\BankSoal;
use App\Models\Ujian;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * One GET route per permission group in routes/admin.php, so the matrix
 * tests below exercise every group without hardcoding the same list twice.
 *
 * @return array<string, string>
 */
function adminRouteSample(): array
{
    $bankSoal = BankSoal::factory()->create();
    $ujian = Ujian::factory()->create();

    return [
        'dashboard' => route('admin.dashboard'),
        'master-data' => route('admin.master'),
        'bank-soal' => route('admin.bank-soal.index'),
        'bank-soal.soal' => route('admin.bank-soal.soal', $bankSoal),
        'ujian.view' => route('admin.ujian.index'),
        'ujian-peserta.manage' => route('admin.ujian.peserta', $ujian),
        'leaderboard.view' => route('admin.ujian.leaderboard', $ujian),
        'peserta.manage' => route('admin.peserta.index'),
        'kartu-peserta.print' => route('admin.kartu-peserta.index'),
        'users.manage' => route('admin.users.index'),
    ];
}

test('an operator is denied master data and bank soal, but allowed the rest', function () {
    actingAsAdminRole('operator');
    $routes = adminRouteSample();

    $this->get($routes['dashboard'])->assertOk();
    $this->get($routes['master-data'])->assertForbidden();
    $this->get($routes['bank-soal'])->assertForbidden();
    $this->get($routes['bank-soal.soal'])->assertForbidden();
    $this->get($routes['ujian.view'])->assertOk();
    $this->get($routes['ujian-peserta.manage'])->assertOk();
    $this->get($routes['leaderboard.view'])->assertOk();
    $this->get($routes['peserta.manage'])->assertOk();
    $this->get($routes['kartu-peserta.print'])->assertOk();
    $this->get($routes['users.manage'])->assertForbidden();
});

test('an admin is allowed everything an operator is denied, except kelola pengguna', function () {
    actingAsAdminRole('admin');
    $routes = adminRouteSample();

    $this->get($routes['dashboard'])->assertOk();
    $this->get($routes['master-data'])->assertOk();
    $this->get($routes['bank-soal'])->assertOk();
    $this->get($routes['bank-soal.soal'])->assertOk();
    $this->get($routes['ujian.view'])->assertOk();
    $this->get($routes['ujian-peserta.manage'])->assertOk();
    $this->get($routes['leaderboard.view'])->assertOk();
    $this->get($routes['peserta.manage'])->assertOk();
    $this->get($routes['kartu-peserta.print'])->assertOk();
    $this->get($routes['users.manage'])->assertForbidden();
});

test('an administrator is allowed everything regardless of permissions', function () {
    actingAsAdmin();
    $routes = adminRouteSample();

    foreach ($routes as $route) {
        $this->get($route)->assertOk();
    }
});

test('a peserta cannot reach any admin route', function () {
    // Not assertForbidden(): a peserta is on a completely separate guard now
    // (see config/auth.php), so /admin/* sees them as simply not logged in
    // there at all, not "logged in with the wrong role" — same redirect a
    // plain guest gets.
    actingAsPeserta();
    $routes = adminRouteSample();

    foreach ($routes as $route) {
        $this->get($route)->assertRedirect(route('admin.login'));
    }
});

test('the sidebar hides master data, bank soal, and kelola pengguna from an operator', function () {
    actingAsAdminRole('operator');

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertDontSee('Master Data');
    $response->assertDontSee('Bank Soal');
    $response->assertDontSee('Kelola Pengguna');
    $response->assertSee('Peserta');
    $response->assertSee('Kartu Peserta');
});

test('the sidebar shows master data and bank soal to an admin, but not kelola pengguna', function () {
    actingAsAdminRole('admin');

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Master Data');
    $response->assertSee('Bank Soal');
    $response->assertDontSee('Kelola Pengguna');
});

test('the sidebar shows kelola pengguna to an administrator', function () {
    actingAsAdmin();

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Kelola Pengguna');
});
