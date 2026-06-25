<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">My Reports</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('status'))
                <div class="p-3 bg-green-50 text-green-700 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            {{-- Upload form --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Upload a Report</h3>
                <p class="text-sm text-gray-500 mb-4">{{ $count }} / {{ $max }} files used. Accepted: PDF, DOC, DOCX, XLS, XLSX.</p>

                <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="flex gap-3 items-start">
                    @csrf
                    <div class="flex-1">
                        <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <x-input-error :messages="$errors->get('file')" class="mt-1" />
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 shrink-0" {{ $count >= $max ? 'disabled' : '' }}>Upload</button>
                </form>
            </div>

            {{-- Reports list --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Report</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">File</th>
                        <th class="px-6 py-3"></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($reports as $report)
                        <tr>
                            <td class="px-6 py-3 font-medium">{{ $report->label }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $report->report_date?->format('j M Y') }}</td>
                            <td class="px-6 py-3 text-gray-400 text-xs">{{ $report->source_file }}</td>
                            <td class="px-6 py-3 text-right">
                                <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Delete this report?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No reports uploaded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-center">
                <a href="{{ route('chat') }}" class="text-sm text-indigo-600 hover:underline">← Back to chat</a>
            </div>
        </div>
    </div>
</x-app-layout>
