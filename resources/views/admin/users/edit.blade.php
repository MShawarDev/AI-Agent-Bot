<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Edit User: {{ $user->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
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
                        <input type="checkbox" id="is_admin" name="is_admin" value="1" class="rounded" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                        <x-input-label for="is_admin" value="Admin" class="mb-0" />
                    </div>
                    <div class="flex justify-between items-center pt-2">
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 text-sm hover:underline">Delete user</button>
                        </form>
                        <div class="flex gap-3">
                            <a href="{{ route('admin.clients.show', $user->client_id) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
