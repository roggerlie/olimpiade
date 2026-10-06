<div>
    <x-common.component-card title="Kartu Peserta" desc="Nama Ketua Pelaksana dan QR tanda tangannya, dicetak di bagian bawah setiap kartu peserta.">
        @if ($statusMessage)
            <x-ui.alert variant="success" class="mb-4">{{ $statusMessage }}</x-ui.alert>
        @endif

        <form wire:submit="simpan" class="grid gap-6 md:grid-cols-[1fr_auto]">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Ketua Pelaksana PSF</label>
                    <input type="text" wire:model="ketuaPelaksana" placeholder="mis. Dr. Nama Lengkap, M.Pd."
                        class="h-11 w-full max-w-md rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <p class="mt-1 text-xs text-gray-400">Kosongkan untuk mencetak garis isian kosong.</p>
                    @error('ketuaPelaksana') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Ganti Gambar QR (opsional)</label>
                    <input type="file" wire:model="qrBaru" accept=".png,.jpg,.jpeg,.webp"
                        class="block w-full max-w-md text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:text-gray-400 dark:file:bg-brand-500/15 dark:file:text-brand-400" />
                    <p class="mt-1 text-xs text-gray-400">PNG/JPG/WEBP persegi, maks. 2MB.</p>
                    <div wire:loading wire:target="qrBaru" class="mt-1 text-xs text-gray-400">Mengunggah...</div>
                    @error('qrBaru') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="simpan,qrBaru">Simpan</x-ui.button>
                    @if (\App\Models\Pengaturan::ambil(\App\Models\Pengaturan::QR_KETUA))
                        <x-ui.button type="button" variant="outline" wire:click="pakaiQrBawaan" wire:confirm="Hapus QR yang diunggah dan kembali ke QR bawaan?">Pakai QR Bawaan</x-ui.button>
                    @endif
                </div>
            </div>

            <div class="text-center">
                <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">QR yang dipakai</p>
                <img src="{{ $qrBaru?->isPreviewable() ? $qrBaru->temporaryUrl() : \App\Models\Pengaturan::urlQrKetua() }}" alt="QR Ketua Pelaksana"
                    class="mx-auto h-36 w-36 rounded-lg border border-gray-200 bg-white object-contain p-1 dark:border-gray-700" />
            </div>
        </form>
    </x-common.component-card>
</div>
