<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @php
            $client = auth()->user()->client;
            $reportCount = $client?->salesReports()->count() ?? 0;
            $convoCount  = \App\Models\Conversation::where('user_id', auth()->id())->count();
        @endphp

        <x-ui.page-header title="Welcome back, {{ auth()->user()->name }}" subtitle="Here's your workspace at a glance.">
            <x-slot:actions>
                <x-ui.btn :href="route('chat')" variant="primary">Open chat</x-ui.btn>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.stat-card label="Sales reports" :value="$reportCount">
                <x-slot:icon><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6h6v6m-9 4h12a2 2 0 002-2V7l-5-4H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></x-slot:icon>
            </x-ui.stat-card>
            <x-ui.stat-card label="Conversations" :value="$convoCount">
                <x-slot:icon><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
            </x-ui.stat-card>
            <x-ui.glass-card class="flex flex-col justify-between">
                <div>
                    <x-ui.section-heading>Quick actions</x-ui.section-heading>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Jump back into your work.</p>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.btn :href="route('chat')" variant="ghost">Chat</x-ui.btn>
                    <x-ui.btn :href="route('reports.index')" variant="ghost">Reports</x-ui.btn>
                </div>
            </x-ui.glass-card>
        </div>
    </div>
</x-app-layout>
