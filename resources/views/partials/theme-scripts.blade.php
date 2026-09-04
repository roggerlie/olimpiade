{{--
    Registers the Alpine `theme` and `sidebar` stores used by every layout.
    Ported from tailadmin/resources/views/layouts/app.blade.php.

    This listens for the `alpine:init` event rather than importing Alpine
    itself, so it works whether Alpine came from our own bundle
    (resources/js/app.js, on non-Livewire pages) or from Livewire's bundled
    Alpine (on admin pages) — see layouts/admin.blade.php vs layouts/cbt.blade.php.
--}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('theme', {
            theme: 'light',
            init() {
                const saved = localStorage.getItem('theme');
                const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                this.theme = saved || system;
                this.updateTheme();
            },
            toggle() {
                this.theme = this.theme === 'light' ? 'dark' : 'light';
                localStorage.setItem('theme', this.theme);
                this.updateTheme();
            },
            updateTheme() {
                document.documentElement.classList.toggle('dark', this.theme === 'dark');
                document.body.classList.toggle('dark', this.theme === 'dark');
            }
        });

        Alpine.store('sidebar', {
            isExpanded: window.innerWidth >= 1280,
            isMobileOpen: false,
            isHovered: false,
            toggleExpanded() {
                this.isExpanded = !this.isExpanded;
                this.isMobileOpen = false;
            },
            toggleMobileOpen() {
                this.isMobileOpen = !this.isMobileOpen;
            },
            setMobileOpen(val) {
                this.isMobileOpen = val;
            },
            setHovered(val) {
                if (window.innerWidth >= 1280 && !this.isExpanded) {
                    this.isHovered = val;
                }
            }
        });
    });
</script>

{{--
    Apply dark mode immediately, before first paint, to avoid a light-mode
    flash. Only touches documentElement (<html>) — matches
    tailadmin/resources/views/layouts/app.blade.php exactly. document.body
    doesn't exist yet at this point (this script runs in <head>, before the
    parser reaches <body>), so touching it here throws
    "Cannot read properties of null" and aborts the whole inline script,
    which is also what was hanging the preloader.
--}}
<script>
    (function () {
        const saved = localStorage.getItem('theme');
        const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        if ((saved || system) === 'dark') {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
