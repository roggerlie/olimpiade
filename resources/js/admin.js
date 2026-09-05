/**
 * Minimal JS entry for admin pages — deliberately does NOT import Alpine
 * (Livewire bundles its own instance; a second one here would conflict —
 * see the comment on @vite(...) in components/layouts/admin.blade.php).
 *
 * Just exposes what the date-picker component needs on window, the same
 * way tailadmin/resources/js/app.js does for its own pages. Alpine
 * directives (x-data, x-init) still work fine here since they're driven by
 * Livewire's own bundled Alpine, not this file.
 */
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.flatpickr = flatpickr;
