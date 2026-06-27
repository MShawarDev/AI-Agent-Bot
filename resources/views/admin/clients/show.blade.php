<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 space-y-6">
        @if(session('status'))
            <div class="rounded-xl bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-500/20 dark:text-emerald-400">
                {{ session('status') }}
            </div>
        @endif

        <x-ui.page-header :title="$client->name">
            <x-slot name="actions">
                <x-ui.btn variant="ghost" :href="route('admin.clients.index')">← Back</x-ui.btn>
                <x-ui.btn :href="route('admin.clients.edit', $client)">Edit</x-ui.btn>
            </x-slot>
        </x-ui.page-header>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.stat-card label="Users" :value="$client->users_count" />
            <x-ui.stat-card label="Reports" :value="$client->sales_reports_count" />
            <x-ui.stat-card label="Conversations" :value="$client->conversations_count" />
        </div>

        {{-- Details --}}
        <x-ui.glass-card>
            <x-ui.section-heading>Client Details</x-ui.section-heading>
            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Slug</dt>
                    <dd class="mt-1 text-slate-800 dark:text-white font-mono">{{ $client->slug }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Bot Name</dt>
                    <dd class="mt-1 text-slate-800 dark:text-white">{{ $client->bot_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Currency</dt>
                    <dd class="mt-1 text-slate-800 dark:text-white">{{ $client->currency ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Theme Mode</dt>
                    <dd class="mt-1 text-slate-800 dark:text-white capitalize">{{ $client->theme_mode ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Background Style</dt>
                    <dd class="mt-1 text-slate-800 dark:text-white capitalize">{{ $client->bg_style ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Brand / Accent Colors</dt>
                    <dd class="mt-1 flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                            <span class="inline-block h-4 w-4 rounded-full ring-1 ring-black/10" style="background: {{ $client->brand_color ?? '#4f46e5' }}"></span>
                            {{ $client->brand_color ?? '#4f46e5' }}
                        </span>
                        <span class="text-slate-400">/</span>
                        @if($client->accent_color)
                        <span class="inline-flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                            <span class="inline-block h-4 w-4 rounded-full ring-1 ring-black/10" style="background: {{ $client->accent_color }}"></span>
                            {{ $client->accent_color }}
                        </span>
                        @else
                        <span class="text-slate-400 dark:text-slate-500">—</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-ui.glass-card>

        {{-- Users --}}
        <x-ui.glass-card :padded="false">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200/60 dark:border-white/10">
                <x-ui.section-heading>Users</x-ui.section-heading>
                <x-ui.btn variant="ghost" :href="route('admin.clients.users.create', $client)">+ Add User</x-ui.btn>
            </div>
            <table class="min-w-full divide-y divide-slate-200/60 text-sm dark:divide-white/10">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 dark:divide-white/10">
                    @forelse($client->users as $user)
                    <tr class="transition hover:bg-white/30 dark:hover:bg-white/5">
                        <td class="px-6 py-3 font-medium text-slate-800 dark:text-white">{{ $user->name }}</td>
                        <td class="px-6 py-3 text-slate-500 dark:text-slate-400">{{ $user->email }}</td>
                        <td class="px-6 py-3">
                            @if($user->is_admin)
                                <x-ui.badge color="brand">Admin</x-ui.badge>
                            @else
                                <x-ui.badge color="slate">User</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <x-ui.btn variant="ghost" :href="route('admin.users.edit', $user)">Edit</x-ui.btn>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">No users yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.glass-card>

        {{-- Reports --}}
        <x-ui.glass-card :padded="false">
            <div class="px-6 py-4 border-b border-slate-200/60 dark:border-white/10">
                <x-ui.section-heading>Reports</x-ui.section-heading>
            </div>
            <table class="min-w-full divide-y divide-slate-200/60 text-sm dark:divide-white/10">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="px-6 py-3">Label</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">File</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 dark:divide-white/10">
                    @forelse($client->salesReports()->orderByDesc('report_date')->get() as $report)
                    <tr class="transition hover:bg-white/30 dark:hover:bg-white/5">
                        <td class="px-6 py-3 text-slate-800 dark:text-white">{{ $report->label }}</td>
                        <td class="px-6 py-3 text-slate-500 dark:text-slate-400">{{ $report->report_date?->toDateString() }}</td>
                        <td class="px-6 py-3 text-slate-500 dark:text-slate-400 text-xs">{{ $report->source_file }}</td>
                        <td class="px-6 py-3 text-right">
                            <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" onsubmit="return confirm('Delete this report?')">
                                @csrf @method('DELETE')
                                <x-ui.btn variant="danger" type="submit">Delete</x-ui.btn>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">No reports yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.glass-card>
    </div>
</x-app-layout>
