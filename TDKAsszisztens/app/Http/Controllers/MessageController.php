<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\RagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use RuntimeException;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, ?Conversation $conversation, RagService $rag): RedirectResponse
    {
        $text = $request->validated('message');

        if (! $conversation?->exists) {
            $conversation = $request->user()->conversations()->create([
                'title' => 'Új beszélgetés',
            ]);
        }

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $text,
        ]);

        if ($conversation->title === 'Új beszélgetés' || $conversation->title === null) {
            $conversation->update(['title' => Str::limit($text, 60, '')]);
        }

        try {
            $responseText = $rag->chat(
                (string) $request->user()->id,
                $this->buildContext($conversation),
            );
        } catch (RuntimeException) {
            return to_route('chat.show', $conversation)->withErrors(['message' => 'Az asszisztens jelenleg nem érhető el. Próbáld újra később.']);
        }

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $responseText,
        ]);

        $conversation->touch();

        return to_route('chat.show', $conversation);
    }

    private function buildContext(Conversation $conversation): string
    {
        $history = $conversation->messages()
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        $last = $history->pop();

        if ($history->isEmpty()) {
            return $last->content;
        }

        $transcript = $history
            ->map(fn (Message $message) => ($message->role === 'user' ? 'Felhasználó' : 'Asszisztens').': '.$message->content)
            ->implode("\n\n");

        return "Korábbi beszélgetés:\n{$transcript}\n\nJelenlegi üzenet: {$last->content}";
    }
}
