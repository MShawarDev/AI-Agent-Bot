<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/40 bg-white/70 backdrop-blur-xl dark:border-white/5 dark:bg-slate-950/60">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('chat') }}" class="flex items-center gap-2">
                        @if(auth()->user()?->client?->logo_path)
                            <img src="{{ Storage::url(auth()->user()->client->logo_path) }}" alt="" class="h-8 w-8 rounded-lg object-cover">
                        @else
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-white font-bold">{{ mb_substr(auth()->user()?->client?->name ?? 'A', 0, 1) }}</span>
                        @endif
                        <span class="hidden text-sm font-semibold text-slate-800 dark:text-white sm:block">{{ auth()->user()?->client?->bot_name ?? config('app.name') }}</span>
                    </a>
                </div>
                <div class="hidden space-x-1 sm:-my-px sm:ms-8 sm:flex sm:items-center">
                    <x-nav-link :href="route('chat')" :active="request()->routeIs('chat')">{{ __('Chat') }}</x-nav-link>
                    <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">{{ __('Reports') }}</x-nav-link>
                    @if(auth()->user()?->is_admin)
                        <x-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')">{{ __('Admin') }}</x-nav-link>
                        <x-nav-link :href="route('admin.usage')" :active="request()->routeIs('admin.usage')">{{ __('Usage') }}</x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm text-slate-600 transition hover:bg-slate-500/10 dark:text-slate-300">
                            <x-ui.avatar :name="Auth::user()->name" class="h-8 w-8 text-xs" />
                            <span class="font-medium">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-500/10">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-white/40 dark:border-white/5 sm:hidden">
        <div class="space-y-1 px-3 pt-2 pb-3">
            <x-responsive-nav-link :href="route('chat')" :active="request()->routeIs('chat')">{{ __('Chat') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">{{ __('Reports') }}</x-responsive-nav-link>
            @if(auth()->user()?->is_admin)
                <x-responsive-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')">{{ __('Admin') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.usage')" :active="request()->routeIs('admin.usage')">{{ __('Usage') }}</x-responsive-nav-link>
            @endif
        </div>
        <div class="border-t border-white/40 px-4 py-3 dark:border-white/5">
            <div class="font-medium text-slate-800 dark:text-white">{{ Auth::user()->name }}</div>
            <div class="text-sm text-slate-500">{{ Auth::user()->email }}</div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
