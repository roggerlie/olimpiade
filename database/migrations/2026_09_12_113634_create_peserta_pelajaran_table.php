<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A peserta's declared interest in competing in a mapel ("minat lomba") —
 * deliberately separate from `peserta_ujian` (which means "registered to a
 * specific exam session"). A peserta can be interested in a mapel long
 * before any Ujian exists for their jenjang+mapel combo; see
 * App\Imports\PesertaImport and the "Daftarkan Peserta yang Berminat"
 * action on the Peserta Ujian admin page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peserta_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->cascadeOnDelete();
            $table->foreignId('pelajaran_id')->constrained('pelajaran')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['peserta_id', 'pelajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta_pelajaran');
    }
};
