@props(['padded' => true])
<div {{ $attributes->class([
        'glass animate-fade-up',
        'p-5 sm:p-6' => $padded,
    ]) }}>
    {{ $slot }}
</div>
