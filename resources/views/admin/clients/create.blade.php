<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <x-ui.page-header title="New Client">
            <x-slot name="actions">
                <x-ui.btn variant="ghost" :href="route('admin.clients.index')">← Back</x-ui.btn>
            </x-slot>
        </x-ui.page-header>

        <x-ui.glass-card>
            <form method="POST" action="{{ route('admin.clients.store') }}" class="space-y-4">
                @csrf
                @include('admin.clients._form')
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.clients.index') }}" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Cancel</a>
                    <x-primary-button>Create Client</x-primary-button>
                </div>
            </form>
        </x-ui.glass-card>
    </div>
</x-app-layout>
