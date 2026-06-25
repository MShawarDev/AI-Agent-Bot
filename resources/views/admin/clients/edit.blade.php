<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Edit Client: {{ $client->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.clients.update', $client) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    @include('admin.clients._form')
                    <div class="flex justify-between items-center pt-2">
                        <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" onsubmit="return confirm('Delete this client and all their data?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 text-sm hover:underline">Delete client</button>
                        </form>
                        <div class="flex gap-3">
                            <a href="{{ route('admin.clients.show', $client) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
