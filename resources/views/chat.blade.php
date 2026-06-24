<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Chatbot</title>

    {{-- Tailwind via CDN — swap for your compiled CSS once you add Vite/Mix --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Alpine.js for reactivity --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Smooth bounce for typing dots */
        @keyframes bounce-dot {
            0%, 80%, 100% { transform: translateY(0); }
            40%            { transform: translateY(-6px); }
        }
        .dot-1 { animation: bounce-dot 1.2s infinite; }
        .dot-2 { animation: bounce-dot 1.2s 0.15s infinite; }
        .dot-3 { animation: bounce-dot 1.2s 0.30s infinite; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-2xl" x-data="chatApp()" x-cloak>

    {{-- ─── Chat container ─── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col" style="height: 620px;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 bg-slate-50 rounded-t-2xl shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-sm text-slate-800" x-text="botName"></p>
                    <p class="text-xs text-slate-400" x-text="messages.length + ' message' + (messages.length !== 1 ? 's' : '')"></p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="showConfig = !showConfig"
                        class="text-xs text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configure
                </button>
                <button @click="messages = []"
                        class="text-xs text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Clear
                </button>
            </div>
        </div>

        {{-- Config panel --}}
        <div x-show="showConfig"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="px-5 py-4 border-b border-slate-100 bg-slate-50 space-y-3 shrink-0">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Bot name</label>
                <input type="text" x-model="botName"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-transparent bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1.5">System prompt</label>
                <textarea x-model="systemPrompt" rows="3"
                          class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-transparent resize-none bg-white"></textarea>
                <p class="text-xs text-slate-400 mt-1">This defines your bot's personality and role. Change it to create different bots for different clients.</p>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-5 space-y-3" x-ref="messages">

            {{-- Empty state --}}
            <div x-show="messages.length === 0 && !loading"
                 class="flex flex-col items-center justify-center h-full text-slate-400 select-none">
                <svg class="w-10 h-10 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="text-sm font-medium text-slate-500">Your chatbot is ready</p>
                <p class="text-xs mt-1">Send a message to start the conversation</p>
            </div>

            {{-- Message bubbles --}}
            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div class="max-w-[78%] px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap break-words"
                         :class="msg.role === 'user'
                            ? 'bg-indigo-600 text-white rounded-2xl rounded-br-sm'
                            : 'bg-slate-100 text-slate-800 rounded-2xl rounded-bl-sm'"
                         x-text="msg.content">
                    </div>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div x-show="loading" class="flex justify-start">
                <div class="bg-slate-100 rounded-2xl rounded-bl-sm px-4 py-3.5">
                    <div class="flex gap-1.5 items-center">
                        <span class="w-2 h-2 bg-slate-400 rounded-full dot-1 inline-block"></span>
                        <span class="w-2 h-2 bg-slate-400 rounded-full dot-2 inline-block"></span>
                        <span class="w-2 h-2 bg-slate-400 rounded-full dot-3 inline-block"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input area --}}
        <div class="px-4 py-3.5 border-t border-slate-100 flex gap-2.5 shrink-0">
            <input type="text"
                   x-model="input"
                   @keydown.enter="sendMessage"
                   placeholder="Type a message…"
                   :disabled="loading"
                   class="flex-1 text-sm border border-slate-200 rounded-xl px-4 py-2.5
                          focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-transparent
                          disabled:opacity-50 disabled:cursor-not-allowed transition-shadow">
            <button @click="sendMessage"
                    :disabled="loading || !input.trim()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium
                           px-5 py-2.5 rounded-xl transition-colors
                           disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5">
                Send
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>

    </div>{{-- end chat container --}}

    {{-- Attribution --}}
    <p class="text-center text-xs text-slate-400 mt-3">Powered by Claude API</p>

</div>{{-- end Alpine wrapper --}}

<script>
function chatApp() {
    return {
        messages:     [],
        input:        '',
        loading:      false,
        showConfig:   false,
        botName:      'Sales Assistant',
        systemPrompt: 'You are a sales-reporting assistant for a salon business. Daily "closeout" sales reports are available through tools. Use list_sales_reports to see which dates exist, and get_sales_report to read the figures for a specific date — only fetch the report(s) a question actually needs. Answer using only the figures in the reports, show amounts in AED, and name the report date(s) you used. If there is no report for a requested date, say so plainly.',

        async sendMessage() {
            if (!this.input.trim() || this.loading) return;

            // Add user message to the conversation
            this.messages.push({ role: 'user', content: this.input.trim() });
            this.input   = '';
            this.loading = true;
            this.scrollToBottom();

            try {
                const response = await fetch('{{ route("chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify({
                        messages:      this.messages,   // full history — gives the AI memory
                        system_prompt: this.systemPrompt,
                    }),
                });

                const data = await response.json();

                if (data.message) {
                    this.messages.push({ role: 'assistant', content: data.message });
                } else {
                    this.messages.push({ role: 'assistant', content: 'Something went wrong: ' + (data.error ?? 'Unknown error') });
                }
            } catch (error) {
                this.messages.push({ role: 'assistant', content: 'Network error — could not reach the server.' });
            }

            this.loading = false;
            this.scrollToBottom();
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

</body>
</html>
