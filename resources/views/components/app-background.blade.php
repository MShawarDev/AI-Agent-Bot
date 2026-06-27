@php($bg = \App\Support\Theme::current()['bg'])
<div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute inset-0 bg-slate-50 dark:bg-slate-950"></div>

    @if($bg === 'solid')
        <div class="absolute inset-0 bg-gradient-to-b from-brand/5 to-transparent"></div>
    @elseif($bg === 'dots')
        <div class="absolute inset-0 opacity-[0.4] dark:opacity-[0.25]"
             style="background-image: radial-gradient(rgb(var(--brand-rgb) / 0.18) 1px, transparent 1px); background-size: 22px 22px;"></div>
    @else {{-- mesh / aurora --}}
        <div class="absolute -top-32 -left-24 h-[28rem] w-[28rem] rounded-full bg-brand/30 blur-3xl
                    {{ $bg === 'aurora' ? 'animate-aurora' : '' }}"></div>
        <div class="absolute top-1/3 -right-24 h-[26rem] w-[26rem] rounded-full bg-accent/30 blur-3xl
                    {{ $bg === 'aurora' ? 'animate-aurora' : '' }}" style="animation-delay: -6s;"></div>
        <div class="absolute -bottom-32 left-1/4 h-[24rem] w-[24rem] rounded-full bg-brand/20 blur-3xl
                    {{ $bg === 'aurora' ? 'animate-aurora' : '' }}" style="animation-delay: -12s;"></div>
    @endif
</div>
