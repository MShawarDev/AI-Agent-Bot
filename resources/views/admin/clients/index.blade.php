<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8">
        @if(session('status'))
            <div class="mb-4 rounded-xl bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-500/20 dark:text-emerald-400">
                {{ session('status') }}
            </div>
        @endif

        <x-ui.page-header title="Clients">
            <x-slot name="actions">
                <x-ui.btn :href="route('admin.clients.create')">+ New Client</x-ui.btn>
            </x-slot>
        </x-ui.page-header>

        @if($clients->isEmpty())
            <x-ui.empty-state title="No clients yet" message="Create your first client to get started.">
                <x-slot name="action">
                    <x-ui.btn :href="route('admin.clients.create')">+ New Client</x-ui.btn>
                </x-slot>
            </x-ui.empty-state>
        @else
            {{-- Desktop table --}}
            <div class="hidden sm:block">
                <x-ui.glass-card :padded="false">
                    <table class="min-w-full divide-y divide-slate-200/60 text-sm dark:divide-white/10">
                        <thead>
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Slug</th>
                                <th class="px-6 py-3">Users</th>
                                <th class="px-6 py-3">Reports</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 dark:divide-white/10">
                            @foreach($clients as $client)
                            <tr class="transition hover:bg-white/30 dark:hover:bg-white/5">
                                <td class="px-6 py-4 font-semibold text-slate-800 dark:text-white">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="hover:text-brand">{{ $client->name }}</a>
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $client->slug }}</td>
                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300">{{ $client->users_count }}</td>
                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300">{{ $client->sales_reports_count }}</td>
                                <td class="px-6 py-4">
                                    <x-ui.badge :color="$client->is_active ? 'emerald' : 'slate'">
                                        {{ $client->is_active ? 'Active' : 'Inactive' }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <x-ui.btn variant="ghost" :href="route('admin.clients.edit', $client)">Edit</x-ui.btn>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.glass-card>
            </div>

            {{-- Mobile stacked cards --}}
            <div class="space-y-3 sm:hidden">
                @foreach($clients as $client)
                <x-ui.glass-card>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-800 dark:text-white">
                                <a href="{{ route('admin.clients.show', $client) }}" class="hover:text-brand">{{ $client->name }}</a>
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $client->slug }}</p>
                        </div>
                        <x-ui.badge :color="$client->is_active ? 'emerald' : 'slate'">
                            {{ $client->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex gap-4 text-sm text-slate-500 dark:text-slate-400">
                            <span>{{ $client->users_count }} users</span>
                            <span>{{ $client->sales_reports_count }} reports</span>
                        </div>
                        <x-ui.btn variant="ghost" :href="route('admin.clients.edit', $client)">Edit</x-ui.btn>
                    </div>
                </x-ui.glass-card>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
