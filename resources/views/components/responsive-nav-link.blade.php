@props(['active' => false])
@php
    $classes = $active
        ? 'block rounded-xl bg-brand/10 px-3 py-2 text-base font-semibold text-brand'
        : 'block rounded-xl px-3 py-2 text-base font-medium text-slate-600 hover:bg-slate-500/10 dark:text-slate-300 dark:hover:text-white';
@endphp
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
