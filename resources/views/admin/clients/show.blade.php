<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">{{ $client->name }}</h2>
            <a href="{{ route('admin.clients.edit', $client) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">Edit</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('status'))
                <div class="p-3 bg-green-50 text-green-700 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            {{-- Stats --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-600">{{ $client->users_count }}</p>
                    <p class="text-sm text-gray-500">Users</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-600">{{ $client->sales_reports_count }}</p>
                    <p class="text-sm text-gray-500">Reports</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-600">{{ $client->conversations_count }}</p>
                    <p class="text-sm text-gray-500">Conversations</p>
                </div>
            </div>

            {{-- Users --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="font-semibold">Users</h3>
                    <a href="{{ route('admin.clients.users.create', $client) }}" class="text-sm text-indigo-600 hover:underline">+ Add user</a>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Email</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Role</th>
                        <th class="px-6 py-3"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($client->users as $user)
                        <tr>
                            <td class="px-6 py-3">{{ $user->name }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-3">{{ $user->is_admin ? 'Admin' : 'User' }}</td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-400">No users.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Reports --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-semibold">Reports</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Label</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">File</th>
                        <th class="px-6 py-3"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($client->salesReports()->orderByDesc('report_date')->get() as $report)
                        <tr>
                            <td class="px-6 py-3">{{ $report->label }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $report->report_date?->toDateString() }}</td>
                            <td class="px-6 py-3 text-gray-500 text-xs">{{ $report->source_file }}</td>
                            <td class="px-6 py-3 text-right">
                                <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" onsubmit="return confirm('Delete this report?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-400">No reports.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
