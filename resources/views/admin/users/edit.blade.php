<x-app-layout>
    <div class="mx-auto max-w-lg px-4 py-8">
        <x-ui.page-header :title="'Edit User: ' . $user->name">
            <x-slot name="actions">
                <x-ui.btn variant="ghost" :href="route('admin.clients.show', $user->client_id)">← Back</x-ui.btn>
            </x-slot>
        </x-ui.page-header>

        <x-ui.glass-card>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $user->name) }}" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $user->email) }}" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password" value="New Password (leave blank to keep)" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirm New Password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_admin" name="is_admin" value="1"
                        class="rounded border-slate-300 text-brand shadow-sm focus:ring-brand/40 dark:border-slate-600 dark:bg-slate-700"
                        {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                    <x-input-label for="is_admin" value="Admin" class="mb-0" />
                </div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button" class="text-sm text-rose-600 hover:text-rose-500 dark:text-rose-400 dark:hover:text-rose-300"
                            onclick="document.getElementById('delete-user-form').submit()">
                        Delete user
                    </button>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.clients.show', $user->client_id) }}"
                            class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Cancel</a>
                        <x-primary-button>Save Changes</x-primary-button>
                    </div>
                </div>
            </form>
        </x-ui.glass-card>

        <form id="delete-user-form" method="POST" action="{{ route('admin.users.destroy', $user) }}"
              onsubmit="return confirm('Delete this user?')">
            @csrf @method('DELETE')
        </form>
    </div>
</x-app-layout>
