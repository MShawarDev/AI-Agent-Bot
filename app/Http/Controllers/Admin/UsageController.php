<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class UsageController extends Controller
{
    public function index()
    {
        // Messages per client over the last 30 days
        $perClient = Client::withCount(['conversations', 'salesReports'])
            ->with(['users' => fn ($q) => $q->select('id', 'client_id', 'name', 'email')])
            ->orderBy('name')
            ->get()
            ->map(function (Client $client) {
                $messageCount = Message::whereHas(
                    'conversation',
                    fn ($q) => $q->where('client_id', $client->id)
                )->count();

                $last30 = Message::whereHas(
                    'conversation',
                    fn ($q) => $q->where('client_id', $client->id)
                )->where('created_at', '>=', now()->subDays(30))->count();

                return [
                    'client' => $client,
                    'messages_total' => $messageCount,
                    'messages_30d' => $last30,
                ];
            });

        // Daily message volume (last 30 days, all clients)
        $dailyVolume = Message::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // API error rate from the log is not easily queryable — surface recent
        // Anthropic failures by checking messages that have no assistant follow-up.
        $totalMessages = Message::count();
        $userMessages = Message::where('role', 'user')->count();
        $assistantMessages = Message::where('role', 'assistant')->count();

        return view('admin.usage', compact('perClient', 'dailyVolume', 'totalMessages', 'userMessages', 'assistantMessages'));
    }
}
