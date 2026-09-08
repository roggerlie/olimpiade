@php
    // A small, stable set of badge colors cycled by pelajaran id, so the same
    // subject always reads the same color across cards without needing a
    // dedicated "warna" column on Pelajaran.
    $warnaMapel = fn (int $pelajaranId) => ['primary', 'success', 'warning'][$pelajaranId % 3];
@endphp

<x-layouts.cbt title="Dashboard">
    {{-- Hero greeting banner --}}
    <div class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 p-6 text-white shadow-theme-lg">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-14 right-16 h-28 w-28 rounded-full bg-white/10"></div>

        <div class="relative flex flex-wrap items-center gap-4">
            <div class="rounded-full ring-4 ring-white/25">
                <x-ui.avatar :name="auth()->user()->name" size="xlarge" />
            </div>
            <div>
                <h1 class="text-lg font-semibold">Halo, {{ auth()->user()->name }}</h1>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-white/80">
                    <span>No. Registrasi {{ $peserta->noreg }}</span>
                    <span class="text-white/40">&middot;</span>
                    <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium">{{ $peserta->jenjang->nama }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Priority card: the one thing most worth acting on right now --}}
    @if ($prioritas)
        <div class="mb-6 rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.25C7.16751 3.25 3.25 7.16751 3.25 12C3.25 16.8325 7.16751 20.75 12 20.75C16.8325 20.75 20.75 16.8325 20.75 12C20.75 7.16751 16.8325 3.25 12 3.25ZM1.75 12C1.75 6.33908 6.33908 1.75 12 1.75C17.6609 1.75 22.25 6.33908 22.25 12C22.25 17.6609 17.6609 22.25 12 22.25C6.33908 22.25 1.75 17.6609 1.75 12ZM12 6.25C12.4142 6.25 12.75 6.58579 12.75 7V11.6893L15.5303 14.4697C15.8232 14.7626 15.8232 15.2374 15.5303 15.5303C15.2374 15.8232 14.7626 15.8232 14.4697 15.5303L11.4697 12.5303C11.329 12.3896 11.25 12.1989 11.25 12V7C11.25 6.58579 11.5858 6.25 12 6.25Z" fill="currentColor" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-brand-600 dark:text-brand-400">
                            {{ $prioritas->waktu_mulai ? 'Sedang kamu kerjakan' : 'Perlu perhatianmu' }}
                        </p>
                        <p class="mt-0.5 font-semibold text-gray-800 dark:text-white/90">{{ $prioritas->ujian->nama }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $prioritas->ujian->sesi_mulai->translatedFormat('d M Y, H:i') }} &ndash; {{ $prioritas->ujian->sesi_selesai->translatedFormat('H:i') }}
                        </p>
                    </div>
                </div>

                @if ($prioritas->waktu_mulai)
                    <a href="{{ route('cbt.ujian.kerjakan', $prioritas) }}"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                        Lanjutkan
                    </a>
                @elseif ($prioritas->ujian->sesiSedangBerlangsung())
                    <form method="POST" action="{{ route('cbt.ujian.mulai', $prioritas) }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                            Mulai Ujian
                        </button>
                    </form>
                @else
                    <x-ui.badge color="light">Belum Dibuka</x-ui.badge>
                @endif
            </div>
        </div>
    @endif

    {{-- Summary stat cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z" fill="currentColor" />
                </svg>
            </div>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Total Ujian</p>
            <h4 class="mt-1 text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $statistik['total'] }}</h4>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M20.7071 5.29289C21.0976 5.68342 21.0976 6.31658 20.7071 6.70711L9.70711 17.7071C9.31658 18.0976 8.68342 18.0976 8.29289 17.7071L3.29289 12.7071C2.90237 12.3166 2.90237 11.6834 3.29289 11.2929C3.68342 10.9024 4.31658 10.9024 4.70711 11.2929L9 15.5858L19.2929 5.29289C19.6834 4.90237 20.3166 4.90237 20.7071 5.29289Z" fill="currentColor" />
                </svg>
            </div>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Selesai</p>
            <h4 class="mt-1 text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $statistik['selesai'] }}</h4>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.25C7.16751 3.25 3.25 7.16751 3.25 12C3.25 16.8325 7.16751 20.75 12 20.75C16.8325 20.75 20.75 16.8325 20.75 12C20.75 7.16751 16.8325 3.25 12 3.25ZM1.75 12C1.75 6.33908 6.33908 1.75 12 1.75C17.6609 1.75 22.25 6.33908 22.25 12C22.25 17.6609 17.6609 22.25 12 22.25C6.33908 22.25 1.75 17.6609 1.75 12ZM12 6.25C12.4142 6.25 12.75 6.58579 12.75 7V11.6893L15.5303 14.4697C15.8232 14.7626 15.8232 15.2374 15.5303 15.5303C15.2374 15.8232 14.7626 15.8232 14.4697 15.5303L11.4697 12.5303C11.329 12.3896 11.25 12.1989 11.25 12V7C11.25 6.58579 11.5858 6.25 12 6.25Z" fill="currentColor" />
                </svg>
            </div>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Belum Selesai</p>
            <h4 class="mt-1 text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $statistik['belumSelesai'] }}</h4>
        </div>
    </div>

    @if (session('error'))
        <x-ui.alert variant="error" class="mb-6">{{ session('error') }}</x-ui.alert>
    @endif

    {{-- The priority card above already covers one ujian — no need to repeat it here. --}}
    @php $daftarUjian = $prioritas ? $pesertaUjian->reject(fn ($su) => $su->id === $prioritas->id) : $pesertaUjian; @endphp

    @if ($pesertaUjian->isEmpty())
        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Daftar Ujian</h2>
        <div class="flex flex-col items-center rounded-2xl border border-gray-200 bg-white p-10 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z" fill="currentColor" />
                </svg>
            </div>
            <p class="font-medium text-gray-700 dark:text-gray-300">Belum ada ujian yang terdaftar</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Hubungi panitia jika kamu merasa ini keliru.</p>
        </div>
    @elseif ($daftarUjian->isNotEmpty())
        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Daftar Ujian</h2>
        <div class="space-y-4">
            @foreach ($daftarUjian as $su)
                <div class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs transition hover:shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-gray-800 dark:text-white/90">{{ $su->ujian->nama }}</p>
                                <x-ui.badge :color="$warnaMapel($su->ujian->pelajaran_id)" size="sm">{{ $su->ujian->pelajaran->nama }}</x-ui.badge>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $su->ujian->sesi_mulai->translatedFormat('d M Y, H:i') }} &ndash; {{ $su->ujian->sesi_selesai->translatedFormat('H:i') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($su->sudahSubmit())
                                <x-ui.badge color="success">Selesai &middot; Nilai {{ $su->nilai }}</x-ui.badge>
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
