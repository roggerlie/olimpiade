{{--
    Full-page backdrop for the peserta login: navy→teal gradient, drifting
    Gold/Biru/Hijau Hujan orbs, a faded 64px grid and slanted teal "rain".
    Fixed to the viewport so it stays put while the brand panel scrolls on
    short screens. Rain drop positions/timings are derived from the index
    (not random) so the markup is stable between requests.
--}}
@php
    $gridStyle = 'background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:64px 64px;'
        .'-webkit-mask-image:radial-gradient(ellipse 80% 70% at 60% 45%,#000 25%,transparent 80%);mask-image:radial-gradient(ellipse 80% 70% at 60% 45%,#000 25%,transparent 80%)';
@endphp

<div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden bg-pbsf-ink">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,#1c356f_0%,transparent_55%),radial-gradient(ellipse_at_bottom_right,#0f3630_0%,transparent_60%),linear-gradient(160deg,#15224a_0%,#0d1530_60%,#0b1a24_100%)]"></div>
    <div class="absolute inset-0" style="{{ $gridStyle }}"></div>

    {{-- Orbs --}}
    <div class="absolute -top-32 -left-24 size-[520px] animate-pbsf-orb-a rounded-full bg-pbsf-blue/30 blur-[110px] motion-reduce:animate-none"></div>
    <div class="absolute top-1/4 right-[-160px] size-[460px] animate-pbsf-orb-b rounded-full bg-pbsf-gold/20 blur-[110px] motion-reduce:animate-none"></div>
    <div class="absolute -bottom-48 left-1/3 size-[480px] animate-pbsf-orb-c rounded-full bg-pbsf-rain/25 blur-[110px] motion-reduce:animate-none"></div>

    {{-- Hijau Hujan: slanted teal streaks --}}
    <div class="absolute -inset-x-1/4 inset-y-0rotate-[14deg] motion-reduce:hidden">
        @for ($i = 0; $i < 32; $i++)
            <span class="absolute top-0 w-px animate-pbsf-rain rounded-full bg-linear-to-b from-transparent via-pbsf-rain/60 to-pbsf-rain"
                style="left: {{ ($i * 37 + 11) % 100 }}%; height: {{ 60 + ($i * 23) % 90 }}px; opacity: {{ 0.25 + (($i * 7) % 10) / 20 }}; animation-duration: {{ 1.4 + (($i * 13) % 10) / 6 }}s; animation-delay: -{{ ($i * 17) % 30 / 10 }}s;"></span>
        @endfor
    </div>
</div>
