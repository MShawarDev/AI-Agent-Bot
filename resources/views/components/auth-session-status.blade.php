@props(['status'])
@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-xl bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-600 dark:text-emerald-400']) }}>
        {{ $status }}
    </div>
@endif
