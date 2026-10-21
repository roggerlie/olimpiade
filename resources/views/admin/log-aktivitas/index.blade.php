<x-layouts.admin title="Log Aktivitas" page-title="Log Aktivitas">
    <div x-data="{ tab: 'log' }">
        <div class="mb-6 flex gap-2 border-b border-gray-200 dark:border-gray-800">
            <button @click="tab = 'log'" :class="tab === 'log' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="border-b-2 px-4 py-2.5 text-sm font-medium">
                Log
            </button>
            <button @click="tab = 'deteksi'" :class="tab === 'deteksi' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="border-b-2 px-4 py-2.5 text-sm font-medium">
                Deteksi Login Ganda
            </button>
        </div>

        <div x-show="tab === 'log'">
            <livewire:admin.log-aktivitas.manager />
        </div>

        <div x-show="tab === 'deteksi'" x-cloak>
            <livewire:admin.log-aktivitas.deteksi lazy />
        </div>
    </div>
</x-layouts.admin>
