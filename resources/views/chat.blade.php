<x-app-layout>
    <x-slot name="title">{{ $client?->bot_name ?? 'Sales Assistant' }}</x-slot>

    @push('styles')
    <style>
        [x-cloak] { display: none !important; }

        @keyframes bounce-dot {
            0%, 80%, 100% { transform: translateY(0); }
            40%            { transform: translateY(-6px); }
        }
        .dot-1 { animation: bounce-dot 1.2s infinite; }
        .dot-2 { animation: bounce-dot 1.2s 0.15s infinite; }
        .dot-3 { animation: bounce-dot 1.2s 0.30s infinite; }

        /* Markdown styling inside assistant bubbles */
        .prose-chat p                    { margin: 0.25rem 0; }
        .prose-chat ul, .prose-chat ol   { margin: 0.25rem 0 0.25rem 1.25rem; }
        .prose-chat table                { border-collapse: collapse; font-size: 0.8rem; margin: 0.5rem 0; width: 100%; }
        .prose-chat th, .prose-chat td   { border: 1px solid rgb(148 163 184 / 0.4); padding: 0.25rem 0.5rem; text-align: left; }
        .prose-chat th                   { background: rgb(148 163 184 / 0.15); }
        .prose-chat strong               { font-weight: 600; }
        .prose-chat code                 { background: rgb(148 163 184 / 0.2); border-radius: 3px; padding: 0 3px; font-size: 0.85em; }
        .prose-chat pre code             { background: none; padding: 0; }
        .prose-chat pre                  { background: rgb(148 163 184 / 0.2); border-radius: 6px; padding: 0.5rem 0.75rem; overflow-x: auto; margin: 0.5rem 0; }
    </style>
    @endpush

    {{-- ─── Chat page ─── --}}
    {{-- Nav bar is 64 px (h-16). We subtract that + vertical padding from viewport height. --}}
    <div class="flex justify-center px-3 py-3 sm:px-6 sm:py-5">

        <div class="w-full max-w-2xl"
             x-data="chatApp({{ json_encode([
                 'initialMessages' => $history->map(fn($m) => ['role' => $m->role, 'content' => $m->content])->values(),
                 'conversationId'  => $conversation?->id,
                 'starterPrompts'  => $client?->starter_prompts ?? [],
                 'sendUrl'         => route('chat.send'),
                 'streamUrl'       => route('chat.stream'),
             ]) }})"
             x-cloak>

            {{-- Chat container — fills remaining viewport height on mobile, fixed on desktop --}}
            <div class="glass-strong flex flex-col overflow-hidden"
                 style="height: calc(100dvh - 4rem - 1.5rem); max-height: 640px; min-height: 420px;">

                {{-- ── Chat header ── --}}
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-white/40 dark:border-white/5 bg-white/40 dark:bg-white/5 rounded-t-2xl shrink-0">
                    <div class="flex items-center gap-3">
                        @if($client?->logo_path)
                            <img src="{{ Storage::url($client->logo_path) }}"
                                 alt="{{ $client->name }}"
                                 class="w-9 h-9 rounded-xl object-cover">
                        @else
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-brand">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                                </svg>
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold text-sm text-slate-800">{{ $client?->bot_name ?? 'Sales Assistant' }}</p>
                            <p class="text-xs text-slate-400"
                               x-text="messages.length + ' message' + (messages.length !== 1 ? 's' : '')"></p>
                        </div>
                    </div>

                    {{-- New conversation button --}}
                    <button @click="newConversation()"
                            class="text-xs text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New chat
                    </button>
                </div>

                {{-- ── Messages ── --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-3" x-ref="messages">

                    {{-- Empty state --}}
                    <div x-show="messages.length === 0 && !loading"
                         class="flex flex-col items-center justify-center h-full text-slate-400 select-none">
                        <svg class="w-10 h-10 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-sm font-medium text-slate-500">{{ $client?->bot_name ?? 'Sales Assistant' }} is ready</p>
                        <p class="text-xs mt-1 mb-4">Send a message to start the conversation</p>

                        <template x-if="starterPrompts.length > 0">
                            <div class="flex flex-col gap-2 w-full max-w-xs">
                                <template x-for="prompt in starterPrompts" :key="prompt">
                                    <button @click="useStarter(prompt)"
                                            class="glass hover:shadow-glow transition text-left p-3 text-sm">
                                        <span x-text="prompt"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Message bubbles --}}
                    <template x-for="(msg, i) in messages" :key="i">
                        <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div class="max-w-[78%] px-4 py-2.5 text-sm leading-relaxed break-words"
                                 :class="msg.role === 'user'
                                    ? 'bg-brand text-white rounded-2xl rounded-br-sm whitespace-pre-wrap'
                                    : 'bg-white/70 dark:bg-white/5 text-slate-800 dark:text-slate-100 border border-white/50 dark:border-white/10 rounded-2xl rounded-bl-sm prose-chat'">
                                <template x-if="msg.role === 'assistant'">
                                    <div x-html="renderMarkdown(msg.content)"></div>
                                </template>
                                <template x-if="msg.role === 'user'">
                                    <span x-text="msg.content"></span>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Typing indicator --}}
                    <div x-show="loading" class="flex justify-start">
                        <div class="bg-white/70 dark:bg-white/5 border border-white/50 dark:border-white/10 rounded-2xl rounded-bl-sm px-4 py-3.5">
                            <div class="flex gap-1.5 items-center">
                                <span class="w-2 h-2 bg-slate-400 rounded-full dot-1 inline-block"></span>
                                <span class="w-2 h-2 bg-slate-400 rounded-full dot-2 inline-block"></span>
                                <span class="w-2 h-2 bg-slate-400 rounded-full dot-3 inline-block"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Input area ── --}}
                <div class="px-4 py-3.5 border-t border-white/40 dark:border-white/5 flex gap-2.5 shrink-0">
                    <input type="text"
                           x-model="input"
                           @keydown.enter="sendMessage"
                           placeholder="Type a message…"
                           :disabled="loading"
                           class="flex-1 text-sm glass-input rounded-xl px-4 py-2.5
                                  focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-transparent
                                  disabled:opacity-50 disabled:cursor-not-allowed transition-shadow">
                    <button @click="sendMessage"
                            :disabled="loading || !input.trim()"
                            class="bg-brand text-white text-sm font-medium
                                   px-5 py-2.5 rounded-xl transition-colors hover:brightness-110
                                   disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5">
                        Send
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>

            </div>{{-- end chat container --}}

            <p class="text-center text-xs text-slate-400 mt-2">
                {{ $client?->name ? 'Powered by '.$client->name : 'Powered by Claude API' }}
            </p>

        </div>{{-- end Alpine wrapper --}}
    </div>

    <script>
    function chatApp({ initialMessages, conversationId, starterPrompts, sendUrl, streamUrl }) {
        return {
            messages:       initialMessages ?? [],
            input:          '',
            loading:        false,
            conversationId: conversationId ?? null,
            starterPrompts: starterPrompts ?? [],
            sendUrl,
            streamUrl,

            useStarter(prompt) {
                this.input = prompt;
                this.sendMessage();
            },

            newConversation() {
                this.messages       = [];
                this.conversationId = null;
                this.input          = '';
            },

            async sendMessage() {
                if (!this.input.trim() || this.loading) return;

                this.messages.push({ role: 'user', content: this.input.trim() });
                this.input   = '';
                this.loading = true;
                this.scrollToBottom();

                const handled = await this._sendStreaming();
                if (!handled) await this._sendBlocking();

                this.loading = false;
                this.scrollToBottom();
            },

            async _sendStreaming() {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                let response;
                try {
                    response = await fetch(this.streamUrl, {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'text/event-stream' },
                        body:    JSON.stringify({ messages: this.messages, conversation_id: this.conversationId }),
                        signal:  AbortSignal.timeout(90000),
                    });
                } catch { return false; }

                if (!response.ok || !response.body) return false;
                if (!(response.headers.get('content-type') ?? '').includes('text/event-stream')) return false;

                const reader  = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '', assistantIdx = null;

                try {
                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });
                        const parts = buffer.split('\n\n');
                        buffer = parts.pop() ?? '';

                        for (const part of parts) {
                            const lines     = part.split('\n');
                            const eventLine = lines.find(l => l.startsWith('event: '));
                            const dataLine  = lines.find(l => l.startsWith('data: '));
                            if (!dataLine) continue;

                            const event = eventLine ? eventLine.slice(7).trim() : 'message';
                            let data;
                            try { data = JSON.parse(dataLine.slice(6)); } catch { continue; }

                            if (event === 'delta' && data.text) {
                                if (assistantIdx === null) {
                                    this.messages.push({ role: 'assistant', content: '' });
                                    assistantIdx = this.messages.length - 1;
                                }
                                this.messages[assistantIdx].content += data.text;
                                this.scrollToBottom();
                            } else if (event === 'done') {
                                if (data.conversation_id) this.conversationId = data.conversation_id;
                            } else if (event === 'error') {
                                const msg = 'Error: ' + (data.error ?? 'Unknown error');
                                if (assistantIdx !== null) this.messages[assistantIdx].content = msg;
                                else this.messages.push({ role: 'assistant', content: msg });
                                return true;
                            }
                        }
                    }
                } catch {
                    if (assistantIdx === null) return false;
                }

                return assistantIdx !== null;
            },

            async _sendBlocking() {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                try {
                    const response = await fetch(this.sendUrl, {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body:    JSON.stringify({ messages: this.messages, conversation_id: this.conversationId }),
                    });
                    const data = await response.json();
                    if (data.message) {
                        this.messages.push({ role: 'assistant', content: data.message });
                        if (data.conversation_id) this.conversationId = data.conversation_id;
                    } else {
                        this.messages.push({ role: 'assistant', content: 'Something went wrong: ' + (data.error ?? 'Unknown error') });
                    }
                } catch {
                    this.messages.push({ role: 'assistant', content: 'Network error — could not reach the server.' });
                }
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.messages;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },
        };
    }
    </script>

</x-app-layout>
