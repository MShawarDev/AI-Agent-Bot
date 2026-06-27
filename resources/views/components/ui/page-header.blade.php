@props(['title', 'subtitle' => null])
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between animate-fade-up">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white sm:text-3xl">{{ $title }}</h1>
        @if($subtitle)<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
