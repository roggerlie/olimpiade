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
        Schema::create('ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_soal_id')->constrained('bank_soal')->restrictOnDelete();
            $table->foreignId('jenjang_id')->constrained('jenjang')->restrictOnDelete();
            $table->foreignId('pelajaran_id')->constrained('pelajaran')->restrictOnDelete();
            $table->string('nama');
            $table->unsignedInteger('jumlah_soal');
            $table->unsignedInteger('durasi_detik');
            $table->dateTime('sesi_mulai');
            $table->dateTime('sesi_selesai');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ujian');
    }
};
