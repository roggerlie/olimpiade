<x-layouts.admin title="Master Data" page-title="Master Data">
    <div x-data="{ tab: 'jenjang' }">
        <div class="mb-6 flex gap-2 border-b border-gray-200 dark:border-gray-800">
            <button @click="tab = 'jenjang'" :class="tab === 'jenjang' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="border-b-2 px-4 py-2.5 text-sm font-medium">
                Jenjang
            </button>
            <button @click="tab = 'pelajaran'" :class="tab === 'pelajaran' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="border-b-2 px-4 py-2.5 text-sm font-medium">
                Pelajaran
            </button>
        </div>

        <div x-show="tab === 'jenjang'">
            <livewire:admin.jenjang.manager />
        </div>

        <div x-show="tab === 'pelajaran'">
            <livewire:admin.pelajaran.manager />
        </div>
    </div>
</x-layouts.admin>
