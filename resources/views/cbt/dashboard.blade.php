<x-layouts.cbt title="Dashboard">
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-800 dark:text-white/90">Halo, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daftar ujian yang terdaftar untukmu.</p>
    </div>

    @if (session('error'))
        <x-ui.alert variant="error" class="mb-6">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($pesertaUjian->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
            Belum ada ujian yang terdaftar untukmu. Hubungi panitia jika kamu merasa ini keliru.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($pesertaUjian as $su)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $su->ujian->nama }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $su->ujian->pelajaran->nama }} ·
                                {{ $su->ujian->sesi_mulai->translatedFormat('d M Y, H:i') }} &ndash; {{ $su->ujian->sesi_selesai->translatedFormat('H:i') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($su->sudahSubmit())
                                <x-ui.badge color="success">Selesai · Nilai {{ $su->nilai }}</x-ui.badge>
                                <a href="{{ route('cbt.ujian.hasil', $su) }}" class="text-sm text-brand-500 hover:text-brand-600">Lihat Hasil</a>
                            @elseif ($su->waktu_mulai)
                                <x-ui.badge color="warning">Sedang Berlangsung</x-ui.badge>
                                <a href="{{ route('cbt.ujian.kerjakan', $su) }}"
                                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                                    Lanjutkan
                                </a>
                            @elseif (! $su->ujian->sesiSedangBerlangsung())
                                <x-ui.badge color="light">
                                    {{ now()->lt($su->ujian->sesi_mulai) ? 'Belum Dibuka' : 'Sesi Ditutup' }}
                                </x-ui.badge>
                            @else
                                <form method="POST" action="{{ route('cbt.ujian.mulai', $su) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                                        Mulai Ujian
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.cbt>
