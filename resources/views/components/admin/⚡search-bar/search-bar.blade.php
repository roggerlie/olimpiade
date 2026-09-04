{{--
    Styled after tailadmin/resources/views/layouts/app-header.blade.php's
    search form (input + ⌘K badge), but wired to a real Livewire search
    instead of being a decorative "Search or type command..." box with
    nowhere to send a query.
--}}
<div class="relative hidden xl:block" x-data="{ open: false }"
    @keydown.window.prevent.meta.k="open = true; $refs.searchInput.focus()"
    @keydown.window.prevent.ctrl.k="open = true; $refs.searchInput.focus()"
    @keydown.escape.window="open = false"
    @click.outside="open = false">
    <div class="relative">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2">
            <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z" fill="" />
            </svg>
        </span>

        <input type="text" x-ref="searchInput" wire:model.live.debounce.300ms="query"
            @focus="open = true" placeholder="Cari peserta atau ujian..." autocomplete="off"
            class="h-11 w-[320px] rounded-lg border border-gray-200 bg-transparent py-2.5 pl-12 pr-14 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30" />

        <button type="button" tabindex="-1" @click="$refs.searchInput.focus()"
            class="absolute right-2.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 px-[7px] py-[4.5px] text-xs -tracking-[0.2px] text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
            <span>⌘</span><span>K</span>
        </button>
    </div>

    <div x-show="open" x-cloak x-transition
        class="absolute left-0 top-full z-50 mt-2 max-h-96 w-full overflow-y-auto rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark">

        @if (mb_strlen($query) < 2)
            <p class="px-3 py-3 text-sm text-gray-500 dark:text-gray-400">Ketik minimal 2 huruf untuk mencari.</p>
        @elseif (! $this->hasResults)
            <p class="px-3 py-3 text-sm text-gray-500 dark:text-gray-400">Tidak ada hasil untuk "{{ $query }}".</p>
        @else
            @if ($this->pesertaResults->isNotEmpty())
                <p class="px-3 pb-1 pt-2 text-xs font-medium uppercase tracking-wide text-gray-400">Peserta</p>
                @foreach ($this->pesertaResults as $peserta)
                    <a href="{{ route('admin.peserta.index', ['q' => $peserta->nama]) }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-white/5">
                        <x-ui.avatar :name="$peserta->nama" size="xsmall" />
                        <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-300">{{ $peserta->nama }}</span>
                        <span class="shrink-0 text-xs text-gray-400">{{ $peserta->noreg }} · {{ $peserta->jenjang->nama }}</span>
                    </a>
                @endforeach
            @endif

            @if ($this->ujianResults->isNotEmpty())
                <p class="px-3 pb-1 pt-2 text-xs font-medium uppercase tracking-wide text-gray-400">Ujian</p>
                @foreach ($this->ujianResults as $ujian)
                    <a href="{{ route('admin.ujian.index') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-white/5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z" fill="currentColor" />
                            </svg>
                        </span>
                        <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-300">{{ $ujian->nama }}</span>
                    </a>
                @endforeach
            @endif
        @endif
    </div>
</div>
