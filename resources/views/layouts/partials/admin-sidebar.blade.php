{{--
    Trimmed port of tailadmin/resources/views/layouts/sidebar.blade.php:
    same expand/collapse/hover behaviour via the Alpine `sidebar` store and
    tailadmin's titled menu groups (App\Support\AdminMenu::groups()), but
    without its nested submenu demo — add that back here if a module ever
    needs it.
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
            <img src="{{ asset('images/logo/gupab.png') }}" alt="CBT Olimpiade" width="32" height="32" class="shrink-0 object-contain" />
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                class="text-lg font-bold text-brand-500">CBT Olimpiade</span>
        </a>
    </div>

    <nav class="flex flex-col gap-6 overflow-y-auto pb-8 duration-300 ease-linear no-scrollbar">
        @foreach (\App\Support\AdminMenu::groups() as $group)
            <div>
                {{-- Section title, matching tailadmin's menu-group header: the
                label when expanded, a plain dash when collapsed. --}}
                <h2 class="mb-4 flex text-xs uppercase leading-5 text-gray-400"
                    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">{{ $group['title'] }}</span>
                    <svg x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen" x-cloak
                        width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M4.25 12C4.25 11.5858 4.58579 11.25 5 11.25H19C19.4142 11.25 19.75 11.5858 19.75 12C19.75 12.4142 19.4142 12.75 19 12.75H5C4.58579 12.75 4.25 12.4142 4.25 12Z" fill="currentColor"/>
                    </svg>
                </h2>

                <ul class="flex flex-col gap-1">
                    @foreach ($group['items'] as $item)
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
            </div>
        @endforeach
    </nav>
</aside>
