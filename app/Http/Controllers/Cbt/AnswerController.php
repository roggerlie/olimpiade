<?php

namespace App\Http\Controllers\Cbt;

use App\Http\Controllers\Controller;
use App\Models\PesertaSoal;
use App\Models\PesertaUjian;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Thin JSON endpoints consumed by Alpine (resources/views/cbt/kerjakan.blade.php)
 * — deliberately not Livewire, so autosave and the timer stay snappy under
 * many concurrent exam-takers. Both actions re-check the deadline
 * server-side on every call: the client-side countdown is display-only.
 */
class AnswerController extends Controller
{
    public function simpan(Request $request, PesertaUjian $pesertaUjian): JsonResponse
    {
        Gate::authorize('view', $pesertaUjian);

        if ($pesertaUjian->sudahSubmit()) {
            return response()->json(['message' => 'Ujian sudah dikumpulkan.'], 422);
        }

        if ($pesertaUjian->waktuHabis()) {
            return response()->json(['message' => 'Waktu ujian sudah habis.'], 422);
        }

        $data = $request->validate([
            'soal_id' => ['required', 'integer'],
            'jawaban' => ['nullable', 'in:A,B,C,D,E'],
        ]);

        $pesertaSoal = PesertaSoal::query()
            ->where('peserta_ujian_id', $pesertaUjian->id)
            ->where('soal_id', $data['soal_id'])
            ->firstOrFail();

        $pesertaSoal->update(['jawaban' => $data['jawaban']]);

        return response()->json(['tersimpan' => true]);
    }

    public function submit(PesertaUjian $pesertaUjian, ScoringService $scoring): JsonResponse
    {
        Gate::authorize('view', $pesertaUjian);

        if (! $pesertaUjian->sudahSubmit()) {
            $scoring->submit($pesertaUjian);
        }

        return response()->json(['redirect' => route('cbt.ujian.hasil', $pesertaUjian)]);
    }
}
