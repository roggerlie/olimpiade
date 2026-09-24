{{--
    Peserta login — "concept A": one dark stage (components/auth/pbsf-backdrop)
    with a glass form card on the left and the PBSF brand panel on the right.
    Styled with fixed pbsf-* tokens and no dark: variants, so it looks the
    same in either theme; the theme toggle is hidden here for that reason.
--}}
<x-layouts.guest title="Login Peserta" bodyClass="cbt-theme" :theme-toggle="false">
    <x-auth.pbsf-backdrop />

    <div class="relative flex min-h-screen w-full flex-col font-outfit text-white lg:flex-row">
        {{-- Form — sticky on lg so it stays centered when the brand panel outgrows short viewports --}}
        <div class="flex w-full flex-1 flex-col lg:sticky lg:top-0 lg:h-screen lg:w-[46%] lg:flex-none xl:w-[42%]">
            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-5 py-10 sm:px-6">
                {{-- Compact branding — the full brand panel is hidden below lg --}}
                <div class="mb-8 flex animate-pbsf-fade-up flex-col items-center text-center motion-reduce:animate-none lg:hidden">
                    <img src="/images/pbsf/logo-pbsf-3d.webp" alt="Panca Budi School Fest Vol. 04" width="816" height="382"
                        class="w-full max-w-[320px] drop-shadow-[0_14px_30px_rgba(0,0,0,.5)]" />
                    <p class="mt-3 text-sm text-[#dfe5f5] italic">“Tempat Dimana Impian Terbentuk”</p>
                </div>

                <div class="relative animate-pbsf-fade-up overflow-hidden rounded-3xl border border-white/10 bg-white/[.06] p-6 shadow-[0_30px_80px_rgba(0,0,0,.45)] backdrop-blur-xl [animation-delay:.05s] motion-reduce:animate-none sm:p-8">
                    {{-- Gold → blue → teal accent along the top edge --}}
                    <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-pbsf-gold via-pbsf-blue to-pbsf-rain"></div>

                    <div class="mb-7">
                        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-pbsf-rain/40 bg-pbsf-rain/10 px-3 py-1 text-xs font-semibold tracking-[.08em] text-[#86dbcc] uppercase">
                            <span class="size-1.5 rounded-full bg-pbsf-rain"></span>
                            Portal CBT Peserta
                        </div>
                        <h1 class="text-3xl leading-tight font-bold">
                            Login <span class="bg-linear-to-r from-pbsf-gold-light to-pbsf-gold-deep bg-clip-text text-transparent">Peserta</span>
                        </h1>
                        <p class="mt-2 text-sm text-[#aab4d4]">
                            Masuk dengan No. Registrasi &amp; password peserta untuk mengerjakan ujian.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div role="alert" class="mb-5 flex gap-3 rounded-xl border border-error-500/40 bg-error-500/10 px-4 py-3 text-sm">
                            <svg class="mt-0.5 size-5 flex-none text-error-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM9 6a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0V6Zm1 8.5a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5Z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-semibold text-error-300">Login gagal</p>
                                <p class="text-error-200">{{ $errors->first() }}</p>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5"
                        x-data="{ loading: false, showPassword: false, capsLock: false }" @submit="loading = true">
                        @csrf

                        <div>
                            <label for="username" class="mb-1.5 block text-sm font-medium text-[#dfe5f5]">No. Registrasi</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-[#8e9acb]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 21a8 8 0 0 0-16 0" /><circle cx="12" cy="8" r="4" />
                                </svg>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                                    placeholder="Masukkan No. Registrasi" autocomplete="username"
                                    class="h-12 w-full rounded-xl border border-white/15 bg-white/[.07] pr-4 pl-12 text-sm text-white transition placeholder:text-white/35 focus:border-pbsf-gold focus:bg-white/10 focus:ring-4 focus:ring-pbsf-gold/20 focus:outline-hidden" />
                            </div>
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-[#dfe5f5]">Password</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-[#8e9acb]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="4" y="11" width="16" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" />
                                </svg>
                                <input :type="showPassword ? 'text' : 'password'" type="password" id="password" name="password" required
                                    placeholder="Masukkan password" autocomplete="current-password"
                                    @keydown="capsLock = $event.getModifierState('CapsLock')" @keyup="capsLock = $event.getModifierState('CapsLock')" @blur="capsLock = false"
                                    class="h-12 w-full rounded-xl border border-white/15 bg-white/[.07] pr-12 pl-12 text-sm text-white transition placeholder:text-white/35 focus:border-pbsf-gold focus:bg-white/10 focus:ring-4 focus:ring-pbsf-gold/20 focus:outline-hidden" />
                                <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                                    class="absolute top-1/2 right-4 -translate-y-1/2 text-[#8e9acb] transition hover:text-white"
                                    :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" aria-label="Tampilkan password">
                                    <svg x-show="!showPassword" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M10.7 5.1A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.2 3.2M6.6 6.6A17.4 17.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6" /><path d="m2 2 20 20" /><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                    </svg>
                                </button>
                            </div>
                            <p x-show="capsLock" x-cloak x-transition.opacity class="mt-2 flex items-center gap-1.5 text-xs font-medium text-pbsf-gold-light">
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8.5 3.3a1.7 1.7 0 0 1 3 0l6 10.6A1.7 1.7 0 0 1 16 16.5H4a1.7 1.7 0 0 1-1.5-2.6l6-10.6ZM10 7a1 1 0 0 0-1 1v3a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                </svg>
                                Caps Lock aktif — password membedakan huruf besar &amp; kecil.
                            </p>
                        </div>

                        <label for="remember" class="flex w-fit cursor-pointer items-center gap-3 text-sm text-[#dfe5f5] select-none">
                            <span class="relative flex">
                                <input type="checkbox" id="remember" name="remember" value="1"
                                    class="peer size-5 cursor-pointer appearance-none rounded-md border border-white/30 bg-white/[.07] transition checked:border-pbsf-gold checked:bg-pbsf-gold focus-visible:ring-4 focus-visible:ring-pbsf-gold/30 focus-visible:outline-hidden" />
                                <svg class="pointer-events-none absolute inset-0 m-auto size-3.5 text-pbsf-navy opacity-0 peer-checked:opacity-100" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                                    <path d="M11.67 3.5 5.25 9.92 2.33 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            Ingat saya
                        </label>

                        <button type="submit" :disabled="loading"
                            class="group relative flex h-12 w-full items-center justify-center gap-2 overflow-hidden rounded-xl bg-linear-to-r from-pbsf-gold-light via-pbsf-gold to-pbsf-gold-deep text-sm font-bold text-pbsf-navy shadow-[0_10px_30px_rgba(255,185,48,.3)] transition hover:shadow-[0_14px_36px_rgba(255,185,48,.45)] focus-visible:ring-4 focus-visible:ring-pbsf-gold/40 focus-visible:outline-hidden disabled:cursor-wait disabled:opacity-80">
                            {{-- Shine sweep on hover --}}
                            <span class="absolute inset-y-0 -left-1/3 w-1/4 -skew-x-12 bg-white/50 blur-sm transition-transform duration-700 ease-out group-hover:translate-x-[520%] group-disabled:hidden motion-reduce:hidden"></span>
                            <svg x-show="loading" x-cloak class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                            </svg>
                            <span class="relative" x-text="loading ? 'Memproses…' : 'Masuk'">Masuk</span>
                            <svg x-show="!loading" class="relative size-4 transition group-hover:translate-x-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-4.2-3.9a.75.75 0 1 1 1.02-1.1l5.5 5.1a.75.75 0 0 1 0 1.1l-5.5 5.1a.75.75 0 1 1-1.02-1.1l4.2-3.9H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </form>
                </div>

                <p class="mt-6 animate-pbsf-fade-up text-center text-xs text-[#8e9acb] [animation-delay:.15s] motion-reduce:animate-none">
                    Panca Budi School Festival Volume 04
                </p>
            </div>
        </div>

        <x-auth.brand-panel :cabang-lomba="$cabangLomba" :jenjang="$jenjang" />
    </div>
</x-layouts.guest>
