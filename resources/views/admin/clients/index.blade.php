<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Clients</h2>
            <a href="{{ route('admin.clients.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">+ New Client</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Name</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Slug</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Users</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Reports</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($clients as $client)
                        <tr>
                            <td class="px-6 py-4 font-medium">{{ $client->name }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $client->slug }}</td>
                            <td class="px-6 py-4">{{ $client->users_count }}</td>
                            <td class="px-6 py-4">{{ $client->sales_reports_count }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-xs {{ $client->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $client->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.clients.show', $client) }}" class="text-indigo-600 hover:underline mr-3">View</a>
                                <a href="{{ route('admin.clients.edit', $client) }}" class="text-indigo-600 hover:underline">Edit</a>
                            </td>
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
