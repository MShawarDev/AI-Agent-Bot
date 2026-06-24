<?php

namespace App\Http\Controllers;

use App\Models\SalesReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    /**
     * The model is asked to look figures up through these tools rather than
     * receiving every report up front — so only the report(s) a question needs
     * are pulled into context, and the raw PDFs never leave the server.
     */
    private const MODEL = 'claude-sonnet-4-6';

    private const MAX_TOOL_ITERATIONS = 5;

    private const DEFAULT_SYSTEM_PROMPT = <<<'PROMPT'
        You are a sales-reporting assistant for a salon business. Daily "closeout"
        sales reports are available through tools. Use `list_sales_reports` to see
        which dates exist, and `get_sales_report` to read the figures for a specific
        date — only fetch the report(s) a question actually needs. Answer using only
        the figures in the reports, show amounts in AED, and name the report date(s)
        you used. If there is no report for a requested date, say so plainly.
        PROMPT;

    public function index()
    {
        return view('chat');
    }

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'messages'           => 'required|array|min:1',
            'messages.*.role'    => 'required|in:user,assistant',
            'messages.*.content' => 'required|string',
            'system_prompt'      => 'nullable|string|max:2000',
        ]);

        $messages = $request->messages;
        $system   = $request->input('system_prompt') ?: self::DEFAULT_SYSTEM_PROMPT;

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model'      => self::MODEL,
                'max_tokens' => 1500,
                'system'     => $system,
                'tools'      => $this->tools(),
                'messages'   => $messages,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'API request failed. Check your ANTHROPIC_API_KEY.',
                ], 500);
            }

            $data    = $response->json();
            $content = $data['content'] ?? [];

            if (($data['stop_reason'] ?? null) === 'tool_use') {
                // Record the assistant's tool request, then answer each tool call locally.
                // An empty tool input arrives as {} but decodes to a PHP [] — coerce it
                // back to an object so the echoed turn re-serialises as {} (the API rejects []).
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
                        $toolResults[] = [
                            'type'        => 'tool_result',
                            'tool_use_id' => $block['id'],
                            'content'     => $this->runTool($block['name'], $block['input'] ?? []),
                        ];
                    }
                }

                $messages[] = ['role' => 'user', 'content' => $toolResults];

                continue;
            }

            // Final answer — extract the text blocks.
            $text = collect($content)
                ->where('type', 'text')
                ->pluck('text')
                ->implode('');

            return response()->json(['message' => $text]);
        }

        return response()->json([
            'error' => 'The assistant took too many steps to answer. Please rephrase.',
        ], 500);
    }

    /**
     * Tool definitions exposed to the model.
     */
    private function tools(): array
    {
        return [
            [
                'name'        => 'list_sales_reports',
                'description' => 'List the daily closeout sales reports that are available, with their dates. Call this first to find which report(s) a question refers to.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => new \stdClass,
                ],
            ],
            [
                'name'        => 'get_sales_report',
                'description' => 'Return the full figures (receipts, VAT, payment types, sales by category, etc.) for one daily closeout report.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'date' => [
                            'type'        => 'string',
                            'description' => 'The report date, ideally as YYYY-MM-DD (e.g. 2026-06-19). A label like "19 Jun 2026" also works.',
                        ],
                    ],
                    'required' => ['date'],
                ],
            ],
        ];
    }

    /**
     * Execute a tool call against the local database and return a string result.
     */
    private function runTool(string $name, array $input): string
    {
        if ($name === 'list_sales_reports') {
            $reports = SalesReport::orderBy('report_date')->get(['label', 'report_date']);

            if ($reports->isEmpty()) {
                return 'No sales reports have been ingested yet.';
            }

            return $reports
                ->map(fn ($r) => $r->label.($r->report_date ? " ({$r->report_date->toDateString()})" : ''))
                ->implode("\n");
        }

        if ($name === 'get_sales_report') {
            $needle = trim((string) ($input['date'] ?? ''));

            $report = SalesReport::query()
                ->when($needle !== '', function ($q) use ($needle) {
                    $q->where('report_date', $needle)
                        ->orWhere('label', 'like', "%{$needle}%")
                        ->orWhere('source_file', 'like', "%{$needle}%");
                })
                ->first();

            if (! $report) {
                $available = SalesReport::orderBy('report_date')->pluck('label')->implode(', ');

                return "No report found for \"{$needle}\". Available reports: {$available}";
            }

            return "Report: {$report->label}\n\n{$report->content}";
        }

        return "Unknown tool: {$name}";
    }
}
