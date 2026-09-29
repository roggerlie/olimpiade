<div class="space-y-6">
    <x-common.component-card title="Reset Data" desc="Hapus data olimpiade per kategori. Akun admin/operator, peran, dan izin tidak ikut terhapus.">
        @if ($hasil)
            <x-ui.alert variant="success" title="Data berhasil dihapus" class="mb-4">
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($hasil['terhapus'] as $tabel => $jumlah)
                        <li>{{ $tabel }}: {{ number_format($jumlah, 0, ',', '.') }} baris</li>
                    @endforeach
                </ul>
                <a href="{{ route('admin.pengaturan.backup', basename($hasil['backup'])) }}" class="mt-2 inline-block font-medium underline">
                    Unduh backup ({{ basename($hasil['backup']) }})
                </a>
            </x-ui.alert>
        @endif

        @if ($this->alasanDitolak)
            <x-ui.alert variant="warning" class="mb-4">{{ $this->alasanDitolak }}</x-ui.alert>
        @endif

        <div class="space-y-3">
            @foreach (\App\Enums\KategoriReset::cases() as $kategori)
                @php($otomatis = array_key_exists($kategori->value, $this->otomatis))
                <label wire:key="kategori-{{ $kategori->value }}"
                    class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 {{ in_array($kategori, $this->kategoriTerpilih, true) ? 'border-error-300 bg-error-50/50 dark:border-error-500/40 dark:bg-error-500/5' : 'border-gray-200 dark:border-gray-800' }}">
                    @if ($otomatis)
                        <input type="checkbox" checked disabled class="mt-0.5 h-4 w-4 rounded border-gray-200 text-error-300 dark:border-gray-800" />
                    @else
                        <input type="checkbox" value="{{ $kategori->value }}" wire:model.live="dipilih"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-error-500 focus:ring-error-500 dark:border-gray-700 dark:bg-white/5" />
                    @endif
                    <span>
                        <span class="block text-sm font-medium text-gray-800 dark:text-white/90">
                            {{ $kategori->label() }}
                            @if ($otomatis)
                                <span class="ml-1 text-xs font-normal text-error-500">(ikut terhapus karena {{ $this->otomatis[$kategori->value] }})</span>
                            @endif
                        </span>
                        <span class="block text-sm text-gray-500 dark:text-gray-400">{{ $kategori->deskripsi() }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('dipilih') <p class="mt-2 text-sm text-error-500">{{ $message }}</p> @enderror

        @if ($this->kategoriTerpilih !== [])
            <div class="mt-5 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <p class="mb-2 text-sm font-medium text-gray-800 dark:text-white/90">Yang akan dihapus:</p>
                <ul class="grid gap-1 text-sm text-gray-600 sm:grid-cols-2 dark:text-gray-400">
                    @foreach ($this->ringkasan as $tabel => $jumlah)
                        <li class="flex justify-between gap-4"><span>{{ $tabel }}</span><span class="font-medium">{{ number_format($jumlah, 0, ',', '.') }}</span></li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Backup Excel otomatis dibuat sebelum data dihapus.</p>
            </div>
        @endif

        <div class="mt-5 flex justify-end">
            <x-ui.button variant="danger" wire:click="konfirmasi" :disabled="$this->kategoriTerpilih === [] || $this->alasanDitolak !== null">
                Hapus Data
            </x-ui.button>
        </div>
    </x-common.component-card>

    <x-common.component-card title="Backup" desc="File Excel yang dibuat otomatis setiap kali data di-reset. Disimpan permanen.">
        @forelse ($this->daftarBackup as $backup)
            <div wire:key="backup-{{ $backup['nama'] }}" class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0 dark:border-gray-800">
                <span class="text-gray-700 dark:text-gray-300">
                    {{ $backup['nama'] }}
                    <span class="ml-2 text-xs text-gray-400">{{ \Illuminate\Support\Number::fileSize($backup['ukuran']) }} · {{ \Carbon\Carbon::createFromTimestamp($backup['waktu'])->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }}</span>
                </span>
                <a href="{{ route('admin.pengaturan.backup', $backup['nama']) }}" class="font-medium text-brand-500 hover:text-brand-600">Unduh</a>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada backup.</p>
        @endforelse
    </x-common.component-card>

    <x-ui.modal wire-model="showKonfirmasi" class="max-w-md m-4">
        <form wire:submit="jalankan" class="max-h-[85vh] overflow-y-auto p-6">
            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Konfirmasi Hapus Data</h3>
            <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                Akan menghapus: <span class="font-medium text-error-500">{{ implode(', ', array_map(fn ($k) => $k->label(), $this->kategoriTerpilih)) }}</span>.
                Tindakan ini tidak bisa dibatalkan.
            </p>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Ketik <span class="font-mono font-semibold text-error-500">{{ $this::FRASA }}</span> untuk melanjutkan
                    </label>
                    <input type="text" wire:model="frasa" autocomplete="off"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('frasa') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Password akun Anda</label>
                    <input type="password" wire:model="password" autocomplete="current-password"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('password') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                @error('dipilih') <x-ui.alert variant="error">{{ $message }}</x-ui.alert> @enderror
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" wire:click="batal">Batal</x-ui.button>
                <x-ui.button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="jalankan">
                    <span wire:loading.remove wire:target="jalankan">Hapus Permanen</span>
                    <span wire:loading wire:target="jalankan">Menghapus...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
