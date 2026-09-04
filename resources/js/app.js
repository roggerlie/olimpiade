import './bootstrap';
import Alpine from 'alpinejs';

/*
 * This bundle only loads on pages without Livewire (guest/auth pages and the
 * CBT exam UI — see resources/views/layouts/cbt.blade.php and guest.blade.php).
 * Admin pages run on Livewire's own bundled Alpine instance instead, so
 * importing this file there would start two Alpine instances.
 */
window.Alpine = Alpine;
Alpine.start();
