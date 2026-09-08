<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('soal', function (Blueprint $table) {
            $table->string('pertanyaan_gambar')->nullable()->after('pertanyaan');
            $table->string('pilih_a_gambar')->nullable()->after('pilih_a');
            $table->string('pilih_b_gambar')->nullable()->after('pilih_b');
            $table->string('pilih_c_gambar')->nullable()->after('pilih_c');
            $table->string('pilih_d_gambar')->nullable()->after('pilih_d');
            $table->string('pilih_e_gambar')->nullable()->after('pilih_e');
        });

        // Pilihan A-D used to be required text; now an option may be image-only
        // (e.g. "which shape is a triangle?"), so its text column has to allow
        // null (pilih_e was already nullable). `pertanyaan` itself stays
        // required — the question always needs some describing text, an image
        // is only ever a supplementary attachment to it, never a replacement.
        Schema::table('soal', function (Blueprint $table) {
            $table->mediumText('pilih_a')->nullable()->change();
            $table->mediumText('pilih_b')->nullable()->change();
            $table->mediumText('pilih_c')->nullable()->change();
            $table->mediumText('pilih_d')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soal', function (Blueprint $table) {
            $table->dropColumn([
                'pertanyaan_gambar',
                'pilih_a_gambar',
                'pilih_b_gambar',
                'pilih_c_gambar',
                'pilih_d_gambar',
                'pilih_e_gambar',
            ]);
        });

        Schema::table('soal', function (Blueprint $table) {
            $table->mediumText('pilih_a')->nullable(false)->change();
            $table->mediumText('pilih_b')->nullable(false)->change();
            $table->mediumText('pilih_c')->nullable(false)->change();
            $table->mediumText('pilih_d')->nullable(false)->change();
        });
    }
};
