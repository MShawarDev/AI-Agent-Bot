@props(['name' => '?', 'src' => null])
@php
    $initials = collect(explode(' ', trim($name)))->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
@if($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->class(['rounded-xl object-cover']) }}>
@else
    <span {{ $attributes->class(['inline-flex items-center justify-center rounded-xl bg-brand font-semibold text-white']) }}>
        {{ $initials ?: '?' }}
    </span>
@endif
