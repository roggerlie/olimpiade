{{-- Ported from tailadmin/resources/views/components/common/preloader.blade.php --}}
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 300)" x-show="show" x-transition.opacity.duration.300ms
    class="fixed inset-0 z-999999 flex items-center justify-center bg-white dark:bg-gray-900">
    <div class="h-10 w-10 animate-spin rounded-full border-4 border-brand-200 border-t-brand-500"></div>
</div>
