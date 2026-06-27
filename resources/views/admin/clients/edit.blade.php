<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <x-ui.page-header :title="'Edit ' . $client->name">
            <x-slot name="actions">
                <x-ui.btn variant="ghost" :href="route('admin.clients.show', $client)">← Back</x-ui.btn>
            </x-slot>
        </x-ui.page-header>

        <x-ui.glass-card>
            <form method="POST" action="{{ route('admin.clients.update', $client) }}" class="space-y-4">
                @csrf @method('PATCH')
                @include('admin.clients._form')
                <div class="flex items-center justify-between pt-2">
                    <button type="button" class="text-sm text-rose-600 hover:text-rose-500 dark:text-rose-400 dark:hover:text-rose-300"
                            onclick="document.getElementById('delete-client-form').submit()">
                        Delete client
                    </button>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.clients.show', $client) }}" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Cancel</a>
                        <x-primary-button>Save Changes</x-primary-button>
                    </div>
                </div>
            </form>
        </x-ui.glass-card>

        <form id="delete-client-form" method="POST" action="{{ route('admin.clients.destroy', $client) }}"
              onsubmit="return confirm('Delete this client and all their data?')">
            @csrf @method('DELETE')
        </form>
    </div>
</x-app-layout>
