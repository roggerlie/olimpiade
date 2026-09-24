<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `noreg` moves from a 7-digit made-up id to a peserta's real 10-digit NISN.
 * `password_plain` is a deliberate, display-only mirror of the last password
 * set on this account (import, admin create/edit, "Reset Password") — never
 * used to authenticate (that's still the hashed `password` column). It
 * exists because peserta have no email for a self-service reset, so the
 * admin needs to hand out credentials directly: shown in Kelola Peserta and
 * printed on the kartu peserta (see resources/views/admin/kartu-peserta).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table): void {
            // Not ->unique() here too: on SQLite, change() rebuilds the
            // table and already carries the existing unique index over —
            // redeclaring it throws "index already exists".
            $table->char('noreg', 10)->change();
            $table->string('password_plain')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table): void {
            $table->dropColumn('password_plain');
            $table->char('noreg', 7)->change();
        });
    }
};
