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
        Schema::create('soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_soal_id')->constrained('bank_soal')->cascadeOnDelete();
            $table->mediumText('pertanyaan');
            $table->mediumText('pilih_a');
            $table->mediumText('pilih_b');
            $table->mediumText('pilih_c');
            $table->mediumText('pilih_d');
            $table->mediumText('pilih_e')->nullable();
            $table->char('jawaban', 1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soal');
    }
};
