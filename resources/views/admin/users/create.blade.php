<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">New User — {{ $client->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
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
                        <input type="checkbox" id="is_admin" name="is_admin" value="1" class="rounded" {{ old('is_admin') ? 'checked' : '' }}>
                        <x-input-label for="is_admin" value="Admin" class="mb-0" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('admin.clients.show', $client) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
