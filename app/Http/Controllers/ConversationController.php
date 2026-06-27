<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->latest()
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return response()->json($conversations);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request, $conversation);

        $messages = $conversation->messages()->orderBy('id')->get(['role', 'content']);

        return response()->json([
            'conversation' => $conversation->only('id', 'title', 'created_at'),
            'messages' => $messages,
        ]);
    }

    public function destroy(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request, $conversation);
        $conversation->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeConversation(Request $request, Conversation $conversation): void
    {
        if ($conversation->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
