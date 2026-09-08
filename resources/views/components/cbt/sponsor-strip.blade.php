{{--
    Sponsor logos for the exam-taking page — its own card at the bottom
    rather than a bare text row, so it reads as a deliberate "supported by"
    section instead of an afterthought tacked onto the page.

    No real logo files exist yet (the 3 GitHub links given for adzkia/bbc/ion
    pointed at a repo that's since gone — 404 even on the bare repo URL, not
    just those file paths). Each entry below falls back to its name as a
    styled chip until a matching file is dropped in public/images/sponsor/ —
    at that point it upgrades to the real logo automatically, no blade
    change needed.
--}}
@php
    $sponsors = [
        ['nama' => 'Adzkia', 'file' => 'adzkia.png'],
        ['nama' => 'BBC', 'file' => 'bbc.png'],
        ['nama' => 'ION', 'file' => 'ion.png'],
    ];
@endphp

<div class="mx-auto mt-6 max-w-5xl rounded-2xl border border-gray-200 bg-white p-6 text-center dark:border-gray-800 dark:bg-white/[0.03]">
    <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Didukung oleh</p>
    <div class="flex flex-wrap items-center justify-center gap-4">
        @foreach ($sponsors as $sponsor)
            @if (file_exists(public_path('images/sponsor/'.$sponsor['file'])))
                <div class="flex h-14 items-center rounded-xl border border-gray-100 bg-gray-50 px-6 dark:border-gray-800 dark:bg-white/5">
                    <img src="{{ asset('images/sponsor/'.$sponsor['file']) }}" alt="{{ $sponsor['nama'] }}" class="h-8 w-auto">
                </div>
            @else
                <div class="flex h-14 items-center rounded-xl border border-dashed border-gray-200 bg-gray-50 px-6 dark:border-gray-700 dark:bg-white/5">
                    <span class="text-sm font-semibold text-gray-400 dark:text-gray-600">{{ $sponsor['nama'] }}</span>
                </div>
            @endif
        @endforeach
    </div>
</div>
