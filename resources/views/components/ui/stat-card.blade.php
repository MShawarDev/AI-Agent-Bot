@props(['label', 'value' => 0, 'iconColor' => 'brand'])
<div class="glass flex items-center gap-4 p-5 animate-fade-up"
     x-data="{ shown: 0, target: {{ (int) $value }} }"
     x-init="
        let reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) { shown = target; return; }
        let start = performance.now(), dur = 900;
        let tick = (t) => { let p = Math.min((t - start) / dur, 1);
            shown = Math.round(p * target); if (p < 1) requestAnimationFrame(tick); };
        requestAnimationFrame(tick);
     ">
    @isset($icon)
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand">{{ $icon }}</div>
    @endisset
    <div>
        <p class="text-2xl font-bold tabular-nums text-slate-800 dark:text-white" x-text="shown.toLocaleString()">{{ number_format((int) $value) }}</p>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
    </div>
</div>
