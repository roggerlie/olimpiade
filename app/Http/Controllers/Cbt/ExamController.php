<?php

namespace App\Http\Controllers\Cbt;

use App\Http\Controllers\Controller;
use App\Models\SiswaUjian;
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
        $siswaUjian = SiswaUjian::query()
            ->whereHas('siswa', fn ($q) => $q->where('user_id', Auth::id()))
            ->with(['ujian.pelajaran', 'ujian.jenjang'])
            ->get()
            ->sortBy('ujian.sesi_mulai');

        return view('cbt.dashboard', ['siswaUjian' => $siswaUjian]);
    }

    /**
     * Start an attempt (draws + freezes the soal order, stamps waktu_mulai)
     * or, if already started, just resume it — never re-rolls a running or
     * finished attempt.
     */
    public function mulai(SiswaUjian $siswaUjian, ExamAttemptService $attempts): RedirectResponse
    {
        Gate::authorize('view', $siswaUjian);

        if ($siswaUjian->sudahSubmit()) {
            return redirect()->route('cbt.ujian.hasil', $siswaUjian);
        }

        if (! $siswaUjian->ujian->sesiSedangBerlangsung()) {
            return back()->with('error', 'Sesi ujian ini belum dibuka atau sudah ditutup.');
        }

        $attempts->mulai($siswaUjian);

        return redirect()->route('cbt.ujian.kerjakan', $siswaUjian);
    }

    /**
     * The exam-taking page. Soal are loaded without their `jawaban` column —
     * the correct answer never reaches the browser, only used server-side
     * at scoring time.
     */
    public function kerjakan(SiswaUjian $siswaUjian, ScoringService $scoring): View|RedirectResponse
    {
        Gate::authorize('view', $siswaUjian);

        if ($siswaUjian->sudahSubmit()) {
            return redirect()->route('cbt.ujian.hasil', $siswaUjian);
        }

        if ($siswaUjian->waktu_mulai === null) {
            return redirect()->route('cbt.dashboard')->with('error', 'Ujian ini belum kamu mulai.');
        }

        if ($siswaUjian->waktuHabis()) {
            $scoring->submit($siswaUjian);

            return redirect()->route('cbt.ujian.hasil', $siswaUjian)
                ->with('error', 'Waktu ujian sudah habis, jawabanmu otomatis dikumpulkan.');
        }

        $siswaUjian->load('ujian');

        // The correct `jawaban` column on Soal is deliberately never selected
        // here — it must not reach the browser before the attempt is scored.
        $soal = $siswaUjian->siswaSoal()
            ->with(['soal:id,pertanyaan,pilih_a,pilih_b,pilih_c,pilih_d,pilih_e'])
            ->orderBy('urutan')
            ->get(['id', 'urutan', 'soal_id', 'jawaban'])
            ->map(fn ($siswaSoal) => [
                'soalId' => $siswaSoal->soal_id,
                'urutan' => $siswaSoal->urutan,
                'pertanyaan' => $siswaSoal->soal->pertanyaan,
                'pilihan' => $siswaSoal->soal->pilihan(),
                'jawabanSaya' => $siswaSoal->jawaban,
            ])
            ->values();

        return view('cbt.kerjakan', [
            'siswaUjian' => $siswaUjian,
            'soal' => $soal,
            'batasWaktu' => $siswaUjian->batasWaktu(),
        ]);
    }

    public function hasil(SiswaUjian $siswaUjian): View|RedirectResponse
    {
        Gate::authorize('view', $siswaUjian);

        if (! $siswaUjian->sudahSubmit()) {
            return redirect()->route('cbt.ujian.kerjakan', $siswaUjian);
        }

        $siswaUjian->load('ujian.pelajaran');

        return view('cbt.hasil', ['siswaUjian' => $siswaUjian]);
    }
}
