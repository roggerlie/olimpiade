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
        Schema::create('peserta_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_ujian_id')->constrained('peserta_ujian')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soal')->restrictOnDelete();
            $table->unsignedInteger('urutan');
            $table->char('jawaban', 1)->nullable();
            $table->timestamps();

            $table->unique(['peserta_ujian_id', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_soal');
    }
};
