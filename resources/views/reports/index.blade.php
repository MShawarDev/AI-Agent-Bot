<x-app-layout>
    <div class="mx-auto max-w-5xl px-4 py-8">
        <x-ui.page-header title="Reports" subtitle="Upload and browse your sales reports." />

        {{-- Status flash --}}
        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400">
                {{ session('status') }}
            </div>
        @endif

        {{-- Upload zone --}}
        <x-ui.glass-card>
            <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data"
                  x-data="{ over: false, file: '' }" class="space-y-4">
                @csrf

                <label @dragover.prevent="over = true" @dragleave.prevent="over = false"
                       @drop.prevent="over = false; $refs.fileInput.files = $event.dataTransfer.files; file = $event.dataTransfer.files[0]?.name ?? ''"
                       class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-10 text-center transition"
                       :class="over ? 'border-brand bg-brand/5' : 'border-slate-300/70 dark:border-white/10'">
                    <svg class="mb-3 h-10 w-10 text-brand" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V18a3 3 0 003 3h12a3 3 0 003-3v-1.5M16.5 7.5L12 3m0 0L7.5 7.5M12 3v13.5"/>
                    </svg>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Drop a report here, or click to browse</span>
                    <span class="mt-1 text-xs text-slate-400" x-text="file || 'PDF, DOC, DOCX, XLS, XLSX, CSV'"></span>
                    <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv" x-ref="fileInput"
                           class="hidden" @change="file = $event.target.files[0]?.name ?? ''">
                </label>

                <x-input-error :messages="$errors->get('file')" />

                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-400">{{ $count }} / {{ $max }} files used. Accepted: PDF, DOC, DOCX, XLS, XLSX, CSV.</p>
                    <button type="submit" {{ $count >= $max ? 'disabled' : '' }}
                            class="inline-flex items-center rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                        Upload report
                    </button>
                </div>
            </form>
        </x-ui.glass-card>

        {{-- Reports listing --}}
        <div class="mt-6">
            @if($reports->isEmpty())
                <x-ui.empty-state title="No reports yet" message="Upload your first sales report to get started." />
            @else
                <x-ui.glass-card :padded="false" class="overflow-hidden">
                    {{-- Desktop table --}}
                    <table class="hidden w-full text-left text-sm sm:table">
                        <thead class="border-b border-white/40 text-xs uppercase text-slate-400 dark:border-white/5">
                            <tr>
                                <th class="px-5 py-3">Report</th>
                                <th class="px-5 py-3">Date</th>
                                <th class="px-5 py-3">File</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $report)
                                <tr class="border-b border-white/30 last:border-0 dark:border-white/5">
                                    <td class="px-5 py-3 font-medium text-slate-700 dark:text-slate-200">{{ $report->label }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $report->report_date?->format('j M Y') }}</td>
                                    <td class="px-5 py-3 text-xs text-slate-400">{{ $report->source_file }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <form method="POST" action="{{ route('reports.destroy', $report) }}"
                                              onsubmit="return confirm('Delete this report?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg px-2.5 py-1 text-xs font-medium text-rose-500 ring-1 ring-inset ring-rose-500/20 transition hover:bg-rose-500/10">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Mobile stacked cards --}}
                    <div class="divide-y divide-white/30 dark:divide-white/5 sm:hidden">
                        @foreach($reports as $report)
                            <div class="px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $report->label }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $report->report_date?->format('j M Y') }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-400">{{ $report->source_file }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('reports.destroy', $report) }}"
                                          onsubmit="return confirm('Delete this report?')" class="shrink-0">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg px-2.5 py-1 text-xs font-medium text-rose-500 ring-1 ring-inset ring-rose-500/20 transition hover:bg-rose-500/10">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.glass-card>
            @endif
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('chat') }}" class="text-sm text-brand hover:underline">← Back to chat</a>
        </div>
    </div>
</x-app-layout>
