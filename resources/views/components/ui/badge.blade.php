@props(['color' => 'slate'])
@php
    $map = [
        'brand'   => 'bg-brand/10 text-brand ring-brand/20',
        'emerald' => 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20 dark:text-emerald-400',
        'rose'    => 'bg-rose-500/10 text-rose-600 ring-rose-500/20 dark:text-rose-400',
        'slate'   => 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $map[$color] ?? $map['slate']]) }}>
    {{ $slot }}
</span>
