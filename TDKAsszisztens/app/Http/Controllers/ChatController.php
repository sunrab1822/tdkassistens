<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\RagService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(Request $request, RagService $rag): Response
    {
        return $this->renderChat($request, null, $rag);
    }

    public function show(Request $request, Conversation $conversation, RagService $rag): Response
    {
        $this->authorize('view', $conversation);

        return $this->renderChat($request, $conversation, $rag);
    }

    private function renderChat(Request $request, ?Conversation $conversation, RagService $rag): Response
    {
        return Inertia::render('Chat', [
            'conversation' => $conversation ? [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'messages' => $conversation->messages()
                    ->oldest('id')
                    ->get(['id', 'conversation_id', 'role', 'content', 'created_at']),
            ] : null,
            'documents' => Inertia::defer(fn () => $rag->documents((string) $request->user()->id)),
        ]);
    }
}
