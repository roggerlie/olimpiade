{{--
    Trimmed port of tailadmin/resources/views/layouts/sidebar.blade.php:
    same expand/collapse/hover behaviour via the Alpine `sidebar` store, but
    a flat item list (App\Support\AdminMenu) instead of tailadmin's nested
    submenu demo — add submenu support back here if a module ever needs it.
--}}
<aside
    class="fixed flex flex-col mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen,
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    <div class="pt-8 pb-7 flex items-center gap-2" :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <img src="/images/logo/logo-icon.svg" alt="CBT Olimpiade" width="32" height="32" class="shrink-0" />
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                class="text-lg font-bold text-brand-500">CBT Olimpiade</span>
        </a>
    </div>

    <nav class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
        <ul class="flex flex-col gap-1">
            @foreach (\App\Support\AdminMenu::items() as $item)
                @php $isActive = request()->routeIs($item['active'] ?? $item['route']); @endphp
                <li>
                    <a href="{{ route($item['route']) }}"
                        class="menu-item group {{ $isActive ? 'menu-item-active' : 'menu-item-inactive' }}"
                        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
                        <span class="{{ $isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                            {!! $item['icon'] !!}
                        </span>
                        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="menu-item-text">
                            {{ $item['name'] }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</aside>
