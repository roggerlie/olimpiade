/**
 * Minimal JS entry for admin pages — deliberately does NOT import Alpine
 * (Livewire bundles its own instance; a second one here would conflict —
 * see the comment on @vite(...) in components/layouts/admin.blade.php).
 *
 * Just exposes what chart-rendering inline scripts need on window, the
 * same way tailadmin/resources/js/app.js does for its own chart pages.
 */
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;
