<?php

use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create an admin user, assign the `admin` role, and log the current test in
 * as that user. Used by Livewire component tests that require an
 * authenticated admin (e.g. tests/Feature/Admin/*).
 *
 * Also seeds the `peserta` role — admin-side flows that create peserta accounts
 * (Admin\Peserta\Manager, App\Imports\PesertaImport) assign it immediately, so it
 * needs to exist even though this actor is the admin, not a student.
 */
function actingAsAdmin(): User
{
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('peserta', 'web');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    test()->actingAs($admin);

    return $admin;
}

/**
 * Create a peserta (with its paired, `peserta`-role login account) and log the
 * current test in as that user. Used by CBT feature tests
 * (tests/Feature/Cbt/*, tests/Feature/Services/*).
 */
function actingAsPeserta(array $attributes = []): Peserta
{
    $peserta = Peserta::factory()->create($attributes);
    test()->actingAs($peserta->user);

    return $peserta;
}
