{{--
    Gold initial avatar for the peserta portal (the portal has no profile
    photos). Size and text size come from the caller's class.
--}}
@props(['name' => ''])

<span {{ $attributes->merge(['class' => 'flex flex-none items-center justify-center rounded-full bg-linear-to-br from-pbsf-gold-light to-pbsf-gold-deep font-bold text-pbsf-navy']) }}>
    {{ $name !== '' ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($name, 0, 1)) : '?' }}
</span>
