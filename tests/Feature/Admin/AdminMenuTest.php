<?php

use App\Support\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrator sees every menu group', function () {
    actingAsAdmin();

    expect(array_column(AdminMenu::groups(), 'title'))->toBe(['Menu', 'Ujian', 'Peserta', 'Sistem'])
        ->and(array_column(AdminMenu::groups()[3]['items'], 'name'))->toBe(['Kelola Pengguna', 'Kelola Peran', 'Pengaturan']);
});

test('a group with no item the user may see is dropped, never left empty', function () {
    actingAsAdminRole('operator');

    $groups = AdminMenu::groups();

    expect(array_column($groups, 'title'))->not->toContain('Sistem');

    foreach ($groups as $group) {
        expect($group['items'])->not->toBeEmpty();
    }
});

test('the sidebar renders the group titles', function () {
    actingAsAdmin();

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Menu', 'Dashboard', 'Ujian', 'Bank Soal', 'Peserta', 'Kartu Peserta', 'Sistem', 'Pengaturan']);
});
