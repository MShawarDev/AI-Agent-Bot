<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">New Client</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.clients.store') }}" class="space-y-4">
                    @csrf
                    @include('admin.clients._form')
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">Create Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
