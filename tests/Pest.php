<?php

use App\Models\Peserta;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
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
 * Create a user, assign the `administrator` role (full access to every
 * /admin permission via the Gate::before super-admin bypass), and log the
 * current test in as that user. Used by Livewire component tests that
 * require an authenticated admin (e.g. tests/Feature/Admin/*) and that
 * aren't specifically testing the admin/operator permission boundary.
 */
function actingAsAdmin(): User
{
    Role::findOrCreate('administrator', 'web');

    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    test()->actingAs($admin);

    return $admin;
}

/**
 * Create a user under the given /admin role (`admin` or `operator`, seeded
 * with that role's real permissions via PermissionSeeder) and log the
 * current test in as that user. Used by permission-boundary tests that
 * need a non-administrator actor — `actingAsAdmin()` bypasses every
 * permission check and can't exercise those boundaries.
 */
function actingAsAdminRole(string $role): User
{
    (new PermissionSeeder)->run();

    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

/**
 * Create a peserta and log the current test in as that peserta, on its own
 * `peserta` guard (see config/auth.php — peserta are no longer a paired
 * User row under a Spatie role, they're Authenticatable in their own
 * right). Used by CBT feature tests (tests/Feature/Cbt/*, tests/Feature/Services/*).
 */
function actingAsPeserta(array $attributes = []): Peserta
{
    $peserta = Peserta::factory()->create($attributes);
    test()->actingAs($peserta, 'peserta');

    return $peserta;
}
