<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function destroy(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('delete', $conversation);

        $conversation->delete();

        return to_route('chat.index');
    }
}
