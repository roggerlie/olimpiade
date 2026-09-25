{{--
    "Didukung oleh" footer for the peserta portal dashboard. Add a sponsor by
    dropping its logo in public/images/pbsf/ and adding a row below.
--}}
@php
    $sponsors = [
        ['nama' => 'Adzkia Kedinasan', 'logo' => '/images/pbsf/adzkia-kedinasan.webp'],
    ];
@endphp

<div class="mt-10 flex flex-col items-center gap-3 text-center">
    <p class="text-xs font-semibold tracking-[.16em] text-[#8e9acb] uppercase">Didukung oleh</p>
    <div class="flex flex-wrap items-center justify-center gap-4">
        @foreach ($sponsors as $sponsor)
            <div class="flex items-center rounded-[14px] bg-white px-6 py-3 shadow-[0_12px_30px_rgba(0,0,0,.3)]">
                <img src="{{ $sponsor['logo'] }}" alt="{{ $sponsor['nama'] }}" class="block h-8" />
            </div>
        @endforeach
    </div>
</div>
