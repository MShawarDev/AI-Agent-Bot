<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300/70 bg-white/60 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-white active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-brand/30 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10']) }}>
    {{ $slot }}
</button>
