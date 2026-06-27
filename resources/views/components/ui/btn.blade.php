@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent disabled:opacity-50 disabled:pointer-events-none';
    $variants = [
        'primary' => 'bg-brand text-white shadow-glow hover:brightness-110 focus:ring-brand/40',
        'ghost'   => 'border border-slate-300/70 bg-white/60 text-slate-700 hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10 focus:ring-brand/30',
        'danger'  => 'bg-rose-600 text-white shadow-sm hover:bg-rose-500 focus:ring-rose-400/40',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
