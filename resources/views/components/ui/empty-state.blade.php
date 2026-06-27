@props(['title', 'message' => null])
<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300/70 px-6 py-12 text-center dark:border-white/10">
    @isset($icon)
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand/10 text-brand">{{ $icon }}</div>
    @endisset
    <h3 class="text-base font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</h3>
    @if($message)<p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $message }}</p>@endif
    @isset($action)<div class="mt-5">{{ $action }}</div>@endisset
</div>
