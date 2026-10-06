<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Room placement is per registration, not per peserta: the same peserta can
 * sit IPA in one room and Matematika in another. Nullable — a fresh
 * registration has no room until the admin places it (by hand or via "Bagi
 * Otomatis"). restrictOnDelete, like the other master data: a room still in
 * use can't be deleted out from under its peserta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta_ujian', function (Blueprint $table) {
            $table->foreignId('ruangan_id')->nullable()->after('ujian_id')->constrained('ruangan')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('peserta_ujian', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ruangan_id');
        });
    }
};
