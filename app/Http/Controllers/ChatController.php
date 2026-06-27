<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\SalesReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    private const DEFAULT_MODEL = 'claude-sonnet-4-6';

    private const MAX_TOOL_ITERATIONS = 5;

    private const DEFAULT_SYSTEM_PROMPT = <<<'PROMPT'
        You are a sales-reporting assistant for a salon business. Daily "closeout"
        sales reports are available through tools. Use `list_sales_reports` to see
        which dates exist, and `get_sales_report` to read the figures for a specific
        date — only fetch the report(s) a question actually needs. Answer using only
        the figures in the reports, show amounts in AED, and name the report date(s)
        you used. If there is no report for a requested date, say so plainly.
        PROMPT;

    // ─── Pages ───────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $client = $request->user()->client;
        $conversation = Conversation::where('user_id', $request->user()->id)->latest()->first();
        $history = $conversation
            ? $conversation->messages()->orderBy('id')->get(['role', 'content'])
            : collect();

        return view('chat', compact('client', 'conversation', 'history'));
    }

    // ─── Blocking endpoint (fallback + default) ───────────────────────────────────

    public function send(Request $request): JsonResponse
    {
        $this->validateChatRequest($request);

        $user = $request->user();
        $client = $user->client;
        $system = $client?->system_prompt ?: self::DEFAULT_SYSTEM_PROMPT;
        $conversation = $this->resolveConversation($request->integer('conversation_id'), $user, $client);
        $messages = $request->messages;

        $this->persistUserMessage($conversation, end($messages));

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $this->callAnthropic($messages, $system);

            if ($response->failed()) {
                $this->logApiError($response, $user, $client);

                return response()->json(['error' => 'API request failed. Check your ANTHROPIC_API_KEY.'], 500);
            }

            $data = $response->json();
            $content = $data['content'] ?? [];

            if (($data['stop_reason'] ?? null) === 'tool_use') {
                [$messages] = $this->handleToolUse($messages, $content, $client?->id);

                continue;
            }

            $text = $this->extractText($content);
            $this->persistAssistantReply($conversation, $text);

            return response()->json(['message' => $text, 'conversation_id' => $conversation->id]);
        }

        return response()->json(['error' => 'The assistant took too many steps. Please rephrase.'], 500);
    }

    // ─── Streaming endpoint (Phase 3, Step 9) ────────────────────────────────────

    public function stream(Request $request): StreamedResponse
    {
        $this->validateChatRequest($request);

        $user = $request->user();
        $client = $user->client;
        $system = $client?->system_prompt ?: self::DEFAULT_SYSTEM_PROMPT;
        $conversation = $this->resolveConversation($request->integer('conversation_id'), $user, $client);
        $messages = $request->messages;

        $this->persistUserMessage($conversation, end($messages));

        return response()->stream(function () use ($messages, $system, $conversation, $user, $client) {
            // Disable any existing output buffering so chunks reach the client immediately
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
                $response = $this->callAnthropic($messages, $system);

                if ($response->failed()) {
                    $this->logApiError($response, $user, $client);
                    $this->sseEmit('error', ['error' => 'API request failed. Check your ANTHROPIC_API_KEY.']);

                    return;
                }

                $data = $response->json();
                $content = $data['content'] ?? [];

                if (($data['stop_reason'] ?? null) === 'tool_use') {
                    [$messages] = $this->handleToolUse($messages, $content, $client?->id);

                    continue;
                }

                $text = $this->extractText($content);
                $this->persistAssistantReply($conversation, $text);

                // Stream the text in small chunks so the client sees it token-by-token.
                // On hosts that buffer SSE (Apache without mod_proxy), all chunks arrive
                // at once — the frontend still works, just without the typing effect.
                foreach (mb_str_split($text, 10) as $chunk) {
                    $this->sseEmit('delta', ['text' => $chunk]);
                    usleep(8000); // ~125 chunks/sec — below Apache's default flush threshold
                }

                $this->sseEmit('done', ['conversation_id' => $conversation->id]);

                return;
            }

            $this->sseEmit('error', ['error' => 'The assistant took too many steps. Please rephrase.']);
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',   // Disable Nginx / LiteSpeed buffering
            'Connection' => 'keep-alive',
        ]);
    }

    // ─── Shared helpers ───────────────────────────────────────────────────────────

    private function validateChatRequest(Request $request): void
    {
        $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string',
            'conversation_id' => 'nullable|integer',
        ]);
    }

    private function resolveConversation(int $id, User $user, ?Client $client): Conversation
    {
        $conversation = $id
            ? Conversation::where('id', $id)->where('user_id', $user->id)->first()
            : null;

        return $conversation ?? Conversation::create([
            'client_id' => $client?->id,
            'user_id' => $user->id,
        ]);
    }

    private function persistUserMessage(Conversation $conversation, array $message): void
    {
        if (($message['role'] ?? null) !== 'user') {
            return;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message['content'],
        ]);

        if (! $conversation->title) {
            $conversation->update(['title' => mb_substr($message['content'], 0, 80)]);
        }
    }

    private function persistAssistantReply(Conversation $conversation, string $text): void
    {
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $text,
        ]);
    }

    private function callAnthropic(array $messages, string $system)
    {
        return Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->when(app()->isLocal(), fn ($h) => $h->withoutVerifying())
            ->timeout(60)->post('https://api.anthropic.com/v1/messages', [
              'model' => config('services.anthropic.model', self::DEFAULT_MODEL),
              'max_tokens' => 4096,
              'system' => $system,
              'tools' => $this->tools(),
              'messages' => $messages,
          ]);
    }

    private function handleToolUse(array $messages, array $content, ?int $clientId): array
    {
        $echoed = array_map(function ($block) {
            if (($block['type'] ?? null) === 'tool_use' && $block['input'] === []) {
                $block['input'] = new \stdClass;
            }

            return $block;
        }, $content);

        $messages[] = ['role' => 'assistant', 'content' => $echoed];

        $toolResults = [];
        foreach ($content as $block) {
            if (($block['type'] ?? null) === 'tool_use') {
                $result = null;

                try {
                    $result = $this->runTool($block['name'], $block['input'] ?? [], $clientId);
                } catch (\Throwable $e) {
                    \Log::warning('Tool execution error', [
                        'tool' => $block['name'],
                        'error' => $e->getMessage(),
                        'client_id' => $clientId,
                    ]);
                    $result = 'Tool error: '.$e->getMessage();
                }

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content' => $result,
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $toolResults];

        return [$messages];
    }

    private function extractText(array $content): string
    {
        return collect($content)->where('type', 'text')->pluck('text')->implode('');
    }

    private function logApiError($response, User $user, ?Client $client): void
    {
        \Log::error('Anthropic API error', [
            'status' => $response->status(),
            'body' => $response->body(),
            'user_id' => $user->id,
            'client_id' => $client?->id,
        ]);
    }

    private function sseEmit(string $event, array $data): void
    {
        echo "event: {$event}\ndata: ".json_encode($data)."\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    // ─── Tool definitions ─────────────────────────────────────────────────────────

    private function tools(): array
    {
        return [
            [
                'name' => 'list_sales_reports',
                'description' => 'List all available sales reports with their labels, dates (if known), and filenames. Always call this first to discover what reports exist before fetching one.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'get_sales_report',
                'description' => 'Fetch the full content of a sales report by any identifying string — a date (e.g. "2026-06-19"), a label (e.g. "19 Jun 2026"), or the filename (e.g. "1000 Sales Records"). Use the label or filename shown by list_sales_reports when the report is a multi-record dataset rather than a single-date closeout. Once you have the content, filter and calculate the answer yourself.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'A date (YYYY-MM-DD or natural language), report label, or filename — any substring that identifies the report.',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    private function runTool(string $name, array $input, ?int $clientId): string
    {
        if ($name === 'list_sales_reports') {
            $reports = SalesReport::where('client_id', $clientId)->orderBy('report_date')->get(['label', 'report_date', 'source_file']);

            if ($reports->isEmpty()) {
                return 'No sales reports have been ingested yet.';
            }

            return $reports->map(function ($r) {
                $line = $r->label;
                if ($r->report_date) {
                    $line .= ' ('.$r->report_date->toDateString().')';
                }
                $line .= ' [file: '.$r->source_file.']';

                return $line;
            })->implode("\n");
        }

        if ($name === 'get_sales_report') {
            $needle = trim((string) ($input['query'] ?? $input['date'] ?? ''));

            $report = SalesReport::where('client_id', $clientId)
                ->when($needle !== '', fn ($q) => $q
                    ->where('report_date', $needle)
                    ->orWhere('label', 'like', "%{$needle}%")
                    ->orWhere('source_file', 'like', "%{$needle}%"))
                ->first();

            if (! $report) {
                $available = SalesReport::where('client_id', $clientId)
                    ->get(['label', 'source_file'])
                    ->map(fn ($r) => "{$r->label} [file: {$r->source_file}]")
                    ->implode(', ');

                return "No report found for \"{$needle}\". Available reports: {$available}";
            }

            return "Report: {$report->label} [file: {$report->source_file}]\n\n{$report->content}";
        }

        return "Unknown tool: {$name}";
    }
}
