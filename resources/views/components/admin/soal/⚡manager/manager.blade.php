<div>
    <x-common.component-card title="Daftar Soal">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari pertanyaan..."
                    class="h-11 w-64 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

                <x-ui.badge color="light">{{ $this->totalSoal }} Soal</x-ui.badge>
            </div>

            @can('bank-soal.manage')
                <div class="flex gap-3">
                    <x-ui.button variant="outline" wire:click="$dispatch('open-soal-import-modal')">Import Soal</x-ui.button>
                    <a href="{{ route('admin.bank-soal.soal.create', $bankSoalId) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3.5 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
                        + Tambah Soal
                    </a>
                </div>
            @endcan
        </div>

        @if ($statusMessage)
            <x-ui.alert variant="success" class="mb-4">{{ $statusMessage }}</x-ui.alert>
        @endif

        @if ($errorMessage)
            <x-ui.alert variant="error" class="mb-4">{{ $errorMessage }}</x-ui.alert>
        @endif

        <div class="space-y-4">
            @forelse ($this->soal as $soal)
                <div wire:key="soal-{{ $soal->id }}" x-data="{ open: false }"
                    class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="mb-1 flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">#{{ $loop->iteration + ($this->soal->currentPage() - 1) * $this->soal->perPage() }}</span>
                                <x-ui.badge color="success">Jawaban: {{ $soal->jawaban }}</x-ui.badge>
                            </div>
                            <p class="text-sm text-gray-800 dark:text-white/90">{{ Str::limit(strip_tags($soal->pertanyaan), 150) }}</p>
                        </div>

                        <div class="flex shrink-0 items-center gap-3 text-sm">
                            <button @click="open = !open" class="flex items-center gap-1 text-gray-500 hover:text-gray-700 dark:text-gray-400">
                                <span x-text="open ? 'Sembunyikan' : 'Lihat Pilihan'"></span>
                                <svg class="h-4 w-4 shrink-0 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            @can('bank-soal.manage')
                                <x-common.table-actions>
                                    <x-common.dropdown-item as="a" href="{{ route('admin.bank-soal.soal.edit', [$bankSoalId, $soal]) }}">Ubah</x-common.dropdown-item>
                                    <x-common.dropdown-item danger wire:click="delete({{ $soal->id }})" wire:confirm="Yakin ingin menghapus soal ini?">Hapus</x-common.dropdown-item>
                                </x-common.table-actions>
                            @endcan
                        </div>
                    </div>

                    <div x-show="open" x-cloak class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <ul class="space-y-1.5">
                            @foreach ($soal->pilihan() as $huruf => $html)
                                <li class="flex items-start gap-2 rounded-lg px-3 py-2 text-sm {{ $huruf === $soal->jawaban ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400' : 'text-gray-600 dark:text-gray-400' }}">
                                    <span class="font-semibold">{{ $huruf }}.</span>
                                    <span class="prose prose-sm max-w-none dark:prose-invert [&_p]:m-0 [&_img]:mt-1 [&_img]:max-h-24 [&_img]:rounded-lg [&_img]:border [&_img]:border-gray-200 dark:[&_img]:border-gray-700">
                                        {!! $html !!}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                    @if ($search)
                        Tidak ada soal yang cocok dengan pencarian "{{ $search }}".
                    @else
                        Belum ada soal di bank ini.
                    @endif
                </div>
            @endforelse
        </div>

        <div>
            {{ $this->soal->links() }}
        </div>
    </x-common.component-card>

    <livewire:admin.soal.import :bank-soal-id="$bankSoalId" />
</div>
