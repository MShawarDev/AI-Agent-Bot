<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 space-y-6">

        <x-ui.page-header title="Usage Overview" subtitle="Message activity across all clients." />

        {{-- Totals --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.stat-card label="Total messages" :value="$totalMessages" />
            <x-ui.stat-card label="User messages" :value="$userMessages" />
            <x-ui.stat-card label="Assistant replies" :value="$assistantMessages" />
        </div>

        {{-- Daily volume chart (simple bar chart via CSS) --}}
        @if($dailyVolume->isNotEmpty())
        <x-ui.glass-card>
            <h3 class="font-semibold text-slate-800 dark:text-white mb-4">Messages per day — last 30 days</h3>
            @php $maxVol = $dailyVolume->max('total') ?: 1; @endphp
            <div class="flex gap-1 h-32">
                @foreach($dailyVolume as $day)
                <div class="flex-1 relative group">
                    <div class="w-full bg-brand rounded-t absolute bottom-0"
                         style="height: {{ round(($day->total / $maxVol) * 100) }}%"
                         title="{{ $day->date }}: {{ $day->total }} messages">
                    </div>
                    <span class="absolute -bottom-5 text-[10px] text-slate-400 rotate-90 origin-left hidden group-hover:inline-block whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($day->date)->format('j M') }}
                    </span>
                </div>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-slate-400 mt-6">
                <span>{{ $dailyVolume->first()->date }}</span>
                <span>{{ $dailyVolume->last()->date }}</span>
            </div>
        </x-ui.glass-card>
        @endif

        {{-- Per-client breakdown --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Per-client breakdown</h2>
                <a href="{{ route('admin.clients.index') }}" class="text-sm text-brand hover:underline">Manage clients →</a>
            </div>

            @if($perClient->isEmpty())
                <x-ui.empty-state title="No clients yet" message="Create your first client to start seeing per-client usage." />
            @else
                {{-- Desktop table --}}
                <div class="hidden sm:block">
                    <x-ui.glass-card :padded="false">
                        <table class="min-w-full divide-y divide-slate-200/60 text-sm dark:divide-white/10">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    <th class="px-6 py-3">Client</th>
                                    <th class="px-6 py-3 text-right">Users</th>
                                    <th class="px-6 py-3 text-right">Reports</th>
                                    <th class="px-6 py-3 text-right">Conversations</th>
                                    <th class="px-6 py-3 text-right">Messages (30 d)</th>
                                    <th class="px-6 py-3 text-right">Messages (total)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/60 dark:divide-white/10">
                                @foreach($perClient as $row)
                                <tr class="transition hover:bg-white/30 dark:hover:bg-white/5">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('admin.clients.show', $row['client']) }}" class="font-medium text-slate-800 hover:text-brand dark:text-white">
                                            {{ $row['client']->name }}
                                        </a>
                                        <span class="text-xs text-slate-400 ml-1">{{ $row['client']->slug }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-slate-700 dark:text-slate-300">{{ $row['client']->users_count }}</td>
                                    <td class="px-6 py-4 text-right text-slate-700 dark:text-slate-300">{{ $row['client']->sales_reports_count }}</td>
                                    <td class="px-6 py-4 text-right text-slate-700 dark:text-slate-300">{{ $row['client']->conversations_count }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-800 dark:text-white">{{ number_format($row['messages_30d']) }}</td>
                                    <td class="px-6 py-4 text-right text-slate-500 dark:text-slate-400">{{ number_format($row['messages_total']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-ui.glass-card>
                </div>

                {{-- Mobile stacked cards --}}
                <div class="space-y-3 sm:hidden">
                    @foreach($perClient as $row)
                    <x-ui.glass-card>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-800 dark:text-white">
                                    <a href="{{ route('admin.clients.show', $row['client']) }}" class="hover:text-brand">{{ $row['client']->name }}</a>
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $row['client']->slug }}</p>
                            </div>
                            <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                                <p class="font-semibold text-base text-slate-800 dark:text-white">{{ number_format($row['messages_30d']) }}</p>
                                <p>msgs (30 d)</p>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-sm text-slate-500 dark:text-slate-400">
                            <div>
                                <p class="text-xs uppercase tracking-wide">Users</p>
                                <p class="font-medium text-slate-700 dark:text-slate-300">{{ $row['client']->users_count }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide">Reports</p>
                                <p class="font-medium text-slate-700 dark:text-slate-300">{{ $row['client']->sales_reports_count }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide">Convos</p>
                                <p class="font-medium text-slate-700 dark:text-slate-300">{{ $row['client']->conversations_count }}</p>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">Total: {{ number_format($row['messages_total']) }} messages</div>
                    </x-ui.glass-card>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
