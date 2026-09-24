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
     * decide what action — if any — to offer, plus a small summary count
     * (total/selesai/belum) for the stat cards above the list.
     */
    public function dashboard(): View
    {
        $peserta = Auth::guard('peserta')->user()->load('jenjang');

        $pesertaUjian = PesertaUjian::query()
            ->where('peserta_id', $peserta->id)
            ->with(['ujian.pelajaran', 'ujian.jenjang'])
            ->get()
            ->sortBy('ujian.sesi_mulai');

        $selesai = $pesertaUjian->filter(fn (PesertaUjian $su) => $su->sudahSubmit())->count();

        // The one thing (if any) most worth a student's attention right now,
        // surfaced in its own card above the plain list: an attempt already
        // in progress outranks everything (they left off mid-exam), then one
        // whose session window is open and just waiting to be started, then
        // simply the soonest upcoming one. Already-submitted attempts never
        // qualify — nothing left to act on there.
        $prioritas = $pesertaUjian->first(fn (PesertaUjian $su) => ! $su->sudahSubmit() && $su->waktu_mulai !== null)
            ?? $pesertaUjian->first(fn (PesertaUjian $su) => ! $su->sudahSubmit() && $su->ujian->sesiSedangBerlangsung())
            ?? $pesertaUjian->first(fn (PesertaUjian $su) => ! $su->sudahSubmit() && now()->lt($su->ujian->sesi_mulai));

        return view('cbt.dashboard', [
            'peserta' => $peserta,
            'pesertaUjian' => $pesertaUjian,
            'prioritas' => $prioritas,
            'statistik' => [
                'total' => $pesertaUjian->count(),
                'selesai' => $selesai,
                'belumSelesai' => $pesertaUjian->count() - $selesai,
            ],
        ]);
    }

    /**
     * Pre-start instructions: exam summary + scoring rule, shown before the
     * timer starts. Only relevant to an attempt that hasn't been started
     * yet — an already-started (or finished) attempt skips straight past it,
     * since re-showing it wouldn't change anything and would just cost the
     * student time off their clock.
     */
    public function petunjuk(PesertaUjian $pesertaUjian): View|RedirectResponse
    {
        $this->authorize($pesertaUjian);

        if ($pesertaUjian->sudahSubmit()) {
            return redirect()->route('cbt.ujian.hasil', $pesertaUjian);
        }

        if ($pesertaUjian->waktu_mulai !== null) {
            return redirect()->route('cbt.ujian.kerjakan', $pesertaUjian);
        }

        if (! $pesertaUjian->ujian->sesiSedangBerlangsung()) {
            return redirect()->route('cbt.dashboard')->with('error', 'Sesi ujian ini belum dibuka atau sudah ditutup.');
        }

        $pesertaUjian->load('ujian.pelajaran');

        return view('cbt.petunjuk', ['pesertaUjian' => $pesertaUjian]);
    }

    /**
     * Start an attempt (draws + freezes the soal order, stamps waktu_mulai)
     * or, if already started, just resume it — never re-rolls a running or
     * finished attempt.
     */
    public function mulai(PesertaUjian $pesertaUjian, ExamAttemptService $attempts): RedirectResponse
    {
        $this->authorize($pesertaUjian);

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
        $this->authorize($pesertaUjian);

        if ($pesertaUjian->sudahSubmit()) {
            return redirect()->route('cbt.ujian.hasil', $pesertaUjian);
        }

        if ($pesertaUjian->waktu_mulai === null) {
            return redirect()->route('cbt.dashboard')->with('error', 'Ujian ini belum kamu mulai.');
        }

        if ($pesertaUjian->waktuHabis()) {
            $scoring->submit($pesertaUjian);

            return redirect()->route('cbt.ujian.hasil', $pesertaUjian)
                ->with('error', 'Waktu ujian sudah habis, jawabanmu otomatis diselesaikan.');
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
        $this->authorize($pesertaUjian);

        if (! $pesertaUjian->sudahSubmit()) {
            return redirect()->route('cbt.ujian.kerjakan', $pesertaUjian);
        }

        $pesertaUjian->load('ujian.pelajaran');

        return view('cbt.hasil', ['pesertaUjian' => $pesertaUjian]);
    }

    /**
     * PesertaUjianPolicy::view() needs the `peserta` guard's user, not the
     * `web` guard Gate::authorize() would otherwise resolve by default.
     */
    private function authorize(PesertaUjian $pesertaUjian): void
    {
        Gate::forUser(Auth::guard('peserta')->user())->authorize('view', $pesertaUjian);
    }
}
