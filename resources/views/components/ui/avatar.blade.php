{{--
    Ported from tailadmin/resources/views/components/ui/avatar.blade.php, plus
    an initials fallback: this app has no profile-photo upload feature yet,
    so `src` is usually empty and we render a brand-colored initial circle
    instead of a broken <img>.
--}}
@props([
    'src' => '',
    'name' => '',
    'alt' => 'User Avatar',
    'size' => 'medium',
    'status' => 'none',
])

@php
    $sizeClasses = [
        'xsmall' => 'h-6 w-6 max-w-6',
        'small' => 'h-8 w-8 max-w-8',
        'medium' => 'h-10 w-10 max-w-10',
        'large' => 'h-12 w-12 max-w-12',
        'xlarge' => 'h-14 w-14 max-w-14',
        'xxlarge' => 'h-16 w-16 max-w-16',
    ];

    $textSizeClasses = [
        'xsmall' => 'text-[10px]',
        'small' => 'text-xs',
        'medium' => 'text-sm',
        'large' => 'text-base',
        'xlarge' => 'text-lg',
        'xxlarge' => 'text-xl',
    ];

    $statusSizeClasses = [
        'xsmall' => 'h-1.5 w-1.5 max-w-1.5',
        'small' => 'h-2 w-2 max-w-2',
        'medium' => 'h-2.5 w-2.5 max-w-2.5',
        'large' => 'h-3 w-3 max-w-3',
        'xlarge' => 'h-3.5 w-3.5 max-w-3.5',
        'xxlarge' => 'h-4 w-4 max-w-4',
    ];

    $statusColorClasses = [
        'online' => 'bg-success-500',
        'offline' => 'bg-error-400',
        'busy' => 'bg-warning-500',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['medium'];
    $textSizeClass = $textSizeClasses[$size] ?? $textSizeClasses['medium'];
    $statusSizeClass = $statusSizeClasses[$size] ?? $statusSizeClasses['medium'];
    $statusColorClass = $statusColorClasses[$status] ?? '';
    $initial = $name !== '' ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($name, 0, 1)) : '?';
@endphp

<div class="relative rounded-full {{ $sizeClass }}">
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="h-full w-full rounded-full object-cover" />
    @else
        <span class="flex h-full w-full items-center justify-center rounded-full bg-brand-500 font-medium text-white {{ $textSizeClass }}">
            {{ $initial }}
        </span>
    @endif

    @if ($status !== 'none')
        <span class="absolute bottom-0 right-0 rounded-full border-[1.5px] border-white dark:border-gray-900 {{ $statusSizeClass }} {{ $statusColorClass }}"></span>
    @endif
</div>
