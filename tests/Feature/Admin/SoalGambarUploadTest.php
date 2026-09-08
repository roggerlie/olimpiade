<?php

use App\Models\BankSoal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('it uploads an image and returns its public url', function () {
    Storage::fake('public');
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    $response = $this->post(route('admin.bank-soal.soal.upload-gambar', $bankSoal), [
        'gambar' => UploadedFile::fake()->image('diagram.png'),
    ]);

    $response->assertOk()->assertJsonStructure(['location']);

    $location = $response->json('location');
    expect($location)->toContain("soal/{$bankSoal->id}/");

    $storedPath = "soal/{$bankSoal->id}/".basename(parse_url($location, PHP_URL_PATH));
    Storage::disk('public')->assertExists($storedPath);
});

test('it rejects a non-image file', function () {
    Storage::fake('public');
    actingAsAdmin();
    $bankSoal = BankSoal::factory()->create();

    $response = $this->postJson(route('admin.bank-soal.soal.upload-gambar', $bankSoal), [
        'gambar' => UploadedFile::fake()->create('soal.pdf', 100),
    ]);

    $response->assertJsonValidationErrors('gambar');
});

test('an operator cannot upload a soal image', function () {
    Storage::fake('public');
    actingAsAdminRole('operator');
    $bankSoal = BankSoal::factory()->create();

    $response = $this->post(route('admin.bank-soal.soal.upload-gambar', $bankSoal), [
        'gambar' => UploadedFile::fake()->image('diagram.png'),
    ]);

    $response->assertForbidden();
});
