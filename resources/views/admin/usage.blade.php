<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Usage Overview</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Totals --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-5 text-center">
                    <p class="text-3xl font-bold text-indigo-600">{{ number_format($totalMessages) }}</p>
                    <p class="text-sm text-gray-500 mt-1">Total messages</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-5 text-center">
                    <p class="text-3xl font-bold text-indigo-600">{{ number_format($userMessages) }}</p>
                    <p class="text-sm text-gray-500 mt-1">User messages</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-5 text-center">
                    <p class="text-3xl font-bold text-indigo-600">{{ number_format($assistantMessages) }}</p>
                    <p class="text-sm text-gray-500 mt-1">Assistant replies</p>
                </div>
            </div>

            {{-- Daily volume chart (simple bar chart via CSS) --}}
            @if($dailyVolume->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-semibold mb-4">Messages per day — last 30 days</h3>
                @php $maxVol = $dailyVolume->max('total') ?: 1; @endphp
                <div class="flex items-end gap-1 h-32">
                    @foreach($dailyVolume as $day)
                    <div class="flex-1 flex flex-col items-center gap-1 group relative">
                        <div class="w-full bg-indigo-400 rounded-t"
                             style="height: {{ round(($day->total / $maxVol) * 100) }}%"
                             title="{{ $day->date }}: {{ $day->total }} messages">
                        </div>
                        <span class="absolute -bottom-5 text-[10px] text-gray-400 rotate-90 origin-left hidden group-hover:inline-block whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($day->date)->format('j M') }}
                        </span>
                    </div>
                    @endforeach
                </div>
                <div class="flex justify-between text-xs text-gray-400 mt-6">
                    <span>{{ $dailyVolume->first()->date }}</span>
                    <span>{{ $dailyVolume->last()->date }}</span>
                </div>
            </div>
            @endif

            {{-- Per-client table --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold">Per-client breakdown</h3>
                    <a href="{{ route('admin.clients.index') }}" class="text-sm text-indigo-600 hover:underline">Manage clients →</a>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Client</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Users</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Reports</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Conversations</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Messages (30 d)</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Messages (total)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($perClient as $row)
                        <tr>
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.clients.show', $row['client']) }}" class="font-medium text-indigo-600 hover:underline">
                                    {{ $row['client']->name }}
                                </a>
                                <span class="text-xs text-gray-400 ml-1">{{ $row['client']->slug }}</span>
                            </td>
                            <td class="px-6 py-3 text-right">{{ $row['client']->users_count }}</td>
                            <td class="px-6 py-3 text-right">{{ $row['client']->sales_reports_count }}</td>
                            <td class="px-6 py-3 text-right">{{ $row['client']->conversations_count }}</td>
                            <td class="px-6 py-3 text-right font-medium">{{ number_format($row['messages_30d']) }}</td>
                            <td class="px-6 py-3 text-right text-gray-500">{{ number_format($row['messages_total']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No clients yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
