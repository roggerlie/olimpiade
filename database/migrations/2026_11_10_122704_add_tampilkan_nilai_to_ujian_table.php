<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-ujian switch for whether peserta see their own nilai/benar/salah right
 * after finishing. Defaults to true so every existing ujian keeps behaving
 * exactly as before; the admin can hide it (and reveal it later) from the
 * Ujian form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujian', function (Blueprint $table): void {
            $table->boolean('tampilkan_nilai')->default(true)->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('ujian', function (Blueprint $table): void {
            $table->dropColumn('tampilkan_nilai');
        });
    }
};
