<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `noreg` goes back from a 10-digit NISN to a 7-digit No. Registrasi, still
 * typed in by the admin (form or import). Existing 10-digit rows would be
 * truncated, so wipe peserta first (`olimpiade:reset-data --only=peserta`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table): void {
            // Not ->unique() here too: on SQLite, change() rebuilds the
            // table and already carries the existing unique index over —
            // redeclaring it throws "index already exists".
            $table->char('noreg', 7)->change();
        });
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table): void {
            $table->char('noreg', 10)->change();
        });
    }
};
