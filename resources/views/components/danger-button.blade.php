<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-rose-400/40']) }}>
    {{ $slot }}
</button>
