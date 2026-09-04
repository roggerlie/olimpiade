<?php

namespace App\Http\Controllers\Cbt;

use App\Http\Controllers\Controller;
use App\Models\PesertaUjian;
use App\Services\ExamAttemptService;
use App\Services\ScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ExamController extends Controller
{
    /**
     * The student's own dashboard: every ujian they're registered for, with
     * enough state (session window, started?, submitted?) for the view to
     * decide what action — if any — to offer.
     */
    public function dashboard(): View
    {
        $pesertaUjian = PesertaUjian::query()
            ->whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()))
            ->with(['ujian.pelajaran', 'ujian.jenjang'])
            ->get()
            ->sortBy('ujian.sesi_mulai');

        return view('cbt.dashboard', ['pesertaUjian' => $pesertaUjian]);
    }

    /**
     * Start an attempt (draws + freezes the soal order, stamps waktu_mulai)
     * or, if already started, just resume it — never re-rolls a running or
     * finished attempt.
     */
    public function mulai(PesertaUjian $pesertaUjian, ExamAttemptService $attempts): RedirectResponse
    {
        Gate::authorize('view', $pesertaUjian);

        if ($pesertaUjian->sudahSubmit()) {
            return redirect()->route('cbt.ujian.hasil', $pesertaUjian);
        }

        if (! $pesertaUjian->ujian->sesiSedangBerlangsung()) {
            return back()->with('error', 'Sesi ujian ini belum dibuka atau sudah ditutup.');
        }

        $attempts->mulai($pesertaUjian);

        return redirect()->route('cbt.ujian.kerjakan', $pesertaUjian);
    }

    /**
     * The exam-taking page. Soal are loaded without their `jawaban` column —
     * the correct answer never reaches the browser, only used server-side
     * at scoring time.
     */
    public function kerjakan(PesertaUjian $pesertaUjian, ScoringService $scoring): View|RedirectResponse
    {
        Gate::authorize('view', $pesertaUjian);

        if ($pesertaUjian->sudahSubmit()) {
            return redirect()->route('cbt.ujian.hasil', $pesertaUjian);
        }

        if ($pesertaUjian->waktu_mulai === null) {
            return redirect()->route('cbt.dashboard')->with('error', 'Ujian ini belum kamu mulai.');
        }

        if ($pesertaUjian->waktuHabis()) {
            $scoring->submit($pesertaUjian);

            return redirect()->route('cbt.ujian.hasil', $pesertaUjian)
                ->with('error', 'Waktu ujian sudah habis, jawabanmu otomatis dikumpulkan.');
        }

        $pesertaUjian->load('ujian');

        // The correct `jawaban` column on Soal is deliberately never selected
        // here — it must not reach the browser before the attempt is scored.
        $soal = $pesertaUjian->pesertaSoal()
            ->with(['soal:id,pertanyaan,pilih_a,pilih_b,pilih_c,pilih_d,pilih_e'])
            ->orderBy('urutan')
            ->get(['id', 'urutan', 'soal_id', 'jawaban'])
            ->map(fn ($pesertaSoal) => [
                'soalId' => $pesertaSoal->soal_id,
                'urutan' => $pesertaSoal->urutan,
                'pertanyaan' => $pesertaSoal->soal->pertanyaan,
                'pilihan' => $pesertaSoal->soal->pilihan(),
                'jawabanSaya' => $pesertaSoal->jawaban,
            ])
            ->values();

        return view('cbt.kerjakan', [
            'pesertaUjian' => $pesertaUjian,
            'soal' => $soal,
            'batasWaktu' => $pesertaUjian->batasWaktu(),
        ]);
    }

    public function hasil(PesertaUjian $pesertaUjian): View|RedirectResponse
    {
        Gate::authorize('view', $pesertaUjian);

        if (! $pesertaUjian->sudahSubmit()) {
            return redirect()->route('cbt.ujian.kerjakan', $pesertaUjian);
        }

        $pesertaUjian->load('ujian.pelajaran');

        return view('cbt.hasil', ['pesertaUjian' => $pesertaUjian]);
    }
}
