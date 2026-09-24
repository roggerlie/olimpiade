<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Peserta accounts stop being a paired `users` row + Spatie `peserta` role
 * and become first-class rows on `peserta` itself (their own `password` /
 * `remember_token`, authenticated via the new `peserta` guard — see
 * config/auth.php). `users` becomes admin-tier only.
 *
 * Dev-stage data only (confirmed with the project owner): no credential
 * backfill — any peserta already registered needs a new password set by an
 * admin afterwards. The `peserta` rows themselves are kept (noreg, nama,
 * jenjang, asal_sekolah untouched), only their login pairing is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->string('password')->nullable()->after('noreg');
            $table->rememberToken()->after('password');
        });

        // Every `users` row that isn't an admin-tier account was only ever
        // the login half of a peserta pairing — safe to remove now that
        // `peserta.user_id` is gone. Cascades to model_has_roles via its own FK.
        DB::table('users')
            ->whereNotIn('id', function ($query): void {
                $query->select('model_id')
                    ->from('model_has_roles')
                    ->where('model_type', User::class)
                    ->whereIn('role_id', function ($q): void {
                        $q->select('id')->from('roles')->whereIn('name', ['administrator', 'admin', 'operator']);
                    });
            })
            ->delete();
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table): void {
            $table->dropColumn(['password', 'remember_token']);
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
