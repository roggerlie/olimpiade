{{-- Ported from tailadmin/resources/views/components/ui/badge.blade.php --}}
@props([
    'variant' => 'light',
    'size' => 'md',
    'color' => 'primary',
])

@php
    $baseStyles = 'inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium capitalize';

    $sizeStyles = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
    ];

    $variants = [
        'light' => [
            'primary' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
            'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
            'error' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
            'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
            'light' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-white/80',
        ],
        'solid' => [
            'primary' => 'bg-brand-500 text-white',
            'success' => 'bg-success-500 text-white',
            'error' => 'bg-error-500 text-white',
            'warning' => 'bg-warning-500 text-white',
            'light' => 'bg-gray-400 dark:bg-white/5 text-white dark:text-white/80',
        ],
    ];

    $colorStyles = $variants[$variant][$color] ?? $variants['light']['primary'];
@endphp

<span class="{{ $baseStyles }} {{ $sizeStyles[$size] ?? $sizeStyles['md'] }} {{ $colorStyles }}" {{ $attributes }}>
    {{ $slot }}
</span>
