{{--
    Ported from tailadmin/resources/views/components/common/preloader.blade.php
    — same spinner size/color (16x16, solid brand-500 ring with a
    transparent leading edge) and dark background as the original. Self-
    contained x-data instead of reading a parent `loaded` variable (this app
    doesn't thread one through every layout that renders this component),
    and a fixed setTimeout instead of waiting on DOMContentLoaded since
    Alpine's x-init already only runs once the DOM exists.
--}}
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 300)" x-show="show" x-transition.opacity.duration.300ms
    class="fixed inset-0 z-999999 flex items-center justify-center bg-white dark:bg-black">
    <div class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent"></div>
</div>
