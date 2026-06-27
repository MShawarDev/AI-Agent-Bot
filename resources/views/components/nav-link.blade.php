@props(['active' => false])
@php
    $classes = $active
        ? 'inline-flex items-center rounded-xl bg-brand/10 px-3.5 py-2 text-sm font-semibold text-brand transition'
        : 'inline-flex items-center rounded-xl px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-500/10 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white';
@endphp
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
