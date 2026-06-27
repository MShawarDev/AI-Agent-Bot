<x-app-layout>
    <div class="mx-auto max-w-lg px-4 py-8">
        <x-ui.page-header :title="'New User — ' . $client->name">
            <x-slot name="actions">
                <x-ui.btn variant="ghost" :href="route('admin.clients.show', $client)">← Back</x-ui.btn>
            </x-slot>
        </x-ui.page-header>

        <x-ui.glass-card>
            <form method="POST" action="{{ route('admin.clients.users.store', $client) }}" class="space-y-4">
                @csrf
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name') }}" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') }}" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password" value="Password" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirm Password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_admin" name="is_admin" value="1"
                        class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700"
                        {{ old('is_admin') ? 'checked' : '' }}>
                    <x-input-label for="is_admin" value="Admin" class="mb-0" />
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.clients.show', $client) }}"
                        class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Cancel</a>
                    <x-primary-button>Create User</x-primary-button>
                </div>
            </form>
        </x-ui.glass-card>
    </div>
</x-app-layout>
