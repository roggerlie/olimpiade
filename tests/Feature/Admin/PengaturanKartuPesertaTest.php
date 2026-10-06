<?php

use App\Models\Pengaturan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('the pengaturan page has a kartu peserta tab prefilled with the saved ketua pelaksana', function () {
    actingAsAdmin();
    Pengaturan::simpan(Pengaturan::KETUA_PELAKSANA, 'Dr. Ketua Lama');

    $this->get(route('admin.pengaturan.index'))
        ->assertOk()
        ->assertSee('Kartu Peserta');

    Livewire::test('admin.pengaturan.kartu-peserta')
        ->assertSet('ketuaPelaksana', 'Dr. Ketua Lama');
});

test('it saves the ketua pelaksana, and clearing it removes the setting', function () {
    actingAsAdmin();

    $component = Livewire::test('admin.pengaturan.kartu-peserta')
        ->set('ketuaPelaksana', '  Dr. Ketua Baru  ')
        ->call('simpan')
        ->assertHasNoErrors();

    expect(Pengaturan::ambil(Pengaturan::KETUA_PELAKSANA))->toBe('Dr. Ketua Baru');

    $component->set('ketuaPelaksana', '')->call('simpan');

    expect(Pengaturan::ambil(Pengaturan::KETUA_PELAKSANA))->toBeNull();
});

test('an uploaded QR replaces the default one, and pakai QR bawaan restores it', function () {
    actingAsAdmin();

    $component = Livewire::test('admin.pengaturan.kartu-peserta')
        ->set('qrBaru', UploadedFile::fake()->image('qr.png', 300, 300))
        ->call('simpan')
        ->assertHasNoErrors();

    $path = Pengaturan::ambil(Pengaturan::QR_KETUA);
    Storage::disk('public')->assertExists($path);
    expect(Pengaturan::urlQrKetua())->toBe(Storage::disk('public')->url($path));

    $component->call('pakaiQrBawaan');

    Storage::disk('public')->assertMissing($path);
    expect(Pengaturan::urlQrKetua())->toBe(asset(Pengaturan::QR_KETUA_BAWAAN));
});

test('it only accepts an image as the QR', function () {
    actingAsAdmin();

    Livewire::test('admin.pengaturan.kartu-peserta')
        ->set('qrBaru', UploadedFile::fake()->create('qr.pdf', 10, 'application/pdf'))
        ->call('simpan')
        ->assertHasErrors(['qrBaru']);

    expect(Pengaturan::ambil(Pengaturan::QR_KETUA))->toBeNull();
});

test('an admin who is not an administrator cannot change kartu peserta settings', function () {
    actingAsAdminRole('admin');

    Livewire::test('admin.pengaturan.kartu-peserta')->assertForbidden();
});
