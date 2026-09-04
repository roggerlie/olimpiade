<x-layouts.admin title="Dashboard" page-title="Dashboard">
    <div class="mb-6">
        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">Selamat datang, {{ auth()->user()->name }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ringkasan aktivitas olimpiade saat ini.</p>
    </div>

    <livewire:admin.dashboard-stats />
</x-layouts.admin>
