<x-layouts.admin title="Pengaturan" page-title="Pengaturan">
    {{-- Tabbed like Master Data so later settings (nama aplikasi, logo, ...)
         can land as extra tabs next to Reset Data. --}}
    <div x-data="{ tab: 'reset-data' }">
        <div class="mb-6 flex gap-2 border-b border-gray-200 dark:border-gray-800">
            <button @click="tab = 'reset-data'" :class="tab === 'reset-data' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="border-b-2 px-4 py-2.5 text-sm font-medium">
                Reset Data
            </button>
            <button @click="tab = 'kartu-peserta'" :class="tab === 'kartu-peserta' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="border-b-2 px-4 py-2.5 text-sm font-medium">
                Kartu Peserta
            </button>
        </div>

        <div x-show="tab === 'reset-data'">
            <livewire:admin.pengaturan.reset-data />
        </div>

        <div x-show="tab === 'kartu-peserta'">
            <livewire:admin.pengaturan.kartu-peserta />
        </div>
    </div>
</x-layouts.admin>
