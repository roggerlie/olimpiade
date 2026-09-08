{{--
    Trimmed port of tailadmin/resources/views/components/common/table-dropdown.blade.php
    (merged with dropdown-menu.blade.php's simpler API): same button/content
    slots and panel styling, but positioned with plain Tailwind + Alpine
    instead of Popper.js. Admin pages don't load resources/js/app.js
    (Livewire brings its own Alpine — see components/layouts/admin.blade.php),
    so there's no window.createPopper to hook into.

    The panel is `fixed` (not `absolute`) and its top/left/right are computed
    from the trigger's own getBoundingClientRect() on open. Row-action menus
    in a table live inside an `overflow-x-auto` wrapper (needed for horizontal
    scroll on narrow screens); if the panel stayed `absolute` there, opening
    it near the bottom row would push it past that wrapper's edge, and per the
    CSS overflow spec a container with overflow-x set to non-visible always
    gets its overflow-y computed as `auto` too (can't be overridden, even with
    `overflow-y: visible !important`) — so the wrapper would grow a spurious
    vertical scrollbar, or worse, clip the menu so its items are unreachable.
    `position: fixed` sidesteps this: a fixed element's containing block is
    the viewport (as long as no ancestor has `transform`/`filter`, true here),
    so it neither gets clipped by nor counts toward an ancestor's scrollable
    overflow — confirmed empirically, not just in theory. This also means we
    don't need x-teleport (tried first, but it desyncs with Livewire's morph:
    the panel's x-show "leaving" transition can get stuck at display:block
    when a wire:click on a menu item re-renders the row mid-transition), so
    the panel stays right where it was in the DOM — click.outside and $refs
    keep working exactly as before.

    Usage:
        <x-common.dropdown-menu>
            <x-slot name="button"> ...trigger... </x-slot>
            <x-slot name="content"> ...menu items... </x-slot>
        </x-common.dropdown-menu>
--}}
@props(['align' => 'right', 'width' => 'w-48'])

<div x-data="{
        open: false,
        top: 0,
        left: 0,
        right: 0,
        position() {
            const r = $refs.trigger.getBoundingClientRect();
            this.top = r.bottom + 8;
            this.left = r.left;
            // document.documentElement.clientWidth, not window.innerWidth: innerWidth
            // includes the vertical scrollbar's own width, but a fixed element's
            // `right` offset is measured from the scrollbar-excluded content edge —
            // using innerWidth here drifts the panel left by the scrollbar's width
            // (~15-17px) on any page tall enough to actually have one.
            this.right = document.documentElement.clientWidth - r.right;
        },
    }"
    @click.outside="open = false"
    @scroll.window="open = false"
    @resize.window="open = false"
    {{-- inline-block, not just relative (block by default): bank-soal's Aksi
        cell puts a "Kelola Soal" link next to this trigger, and a block-level
        wrapper always drops to its own line below inline siblings, stacking
        the link and the kebab menu instead of sitting side by side. --}}
    class="relative inline-block">
    <div x-ref="trigger" @click="position(); open = !open" class="cursor-pointer">
        {{ $button }}
    </div>

    <div x-show="open" x-cloak x-transition
        :style="{ top: top + 'px', {{ $align === 'right' ? 'right' : 'left' }}: {{ $align === 'right' ? 'right' : 'left' }} + 'px' }"
        class="fixed z-50 {{ $width }} space-y-1 rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark"
        role="menu" @click="open = false">
        {{ $content }}
    </div>
</div>
