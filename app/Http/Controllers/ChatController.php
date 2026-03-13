<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\KnowledgeBase;
use App\Services\AiChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Jobs\GenerateChatTitle;
use App\Http\Resources\Chat\ChatResource;
use App\Http\Resources\Chat\ChatLiteResource;

class ChatController extends Controller
{
    public function index(KnowledgeBase $knowledgeBase)
    {
        if ($knowledgeBase->owner_id !== Auth::id()) {
            abort(403);
        }

        $chats = Chat::query()
            ->where('knowledge_base_id', $knowledgeBase->id)
            ->where('owner_id', Auth::id())
            ->latest()
            ->get();

        return ChatLiteResource::collection($chats);
    }

    public function show(Request $request, string $chatId)
    {
        $chat = Chat::query()
            ->with([
                'messages' => fn($q) => $q->orderBy('created_at')
            ])
            ->where('id', $chatId)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        return new ChatResource($chat);
    }

    public function send(Request $request, KnowledgeBase $knowledgeBase, AiChatService $ai)
    {
        $request->validate([
            'message' => 'required|string',
            'chat_id' => 'nullable|uuid',
        ]);

        if ($knowledgeBase->owner_id !== Auth::id()) {
            abort(403);
        }

        $chat = null;

        if ($request->filled('chat_id')) {
            $chat = Chat::query()
                ->where('id', $request->string('chat_id'))
                ->where('knowledge_base_id', $knowledgeBase->id)
                ->where('owner_id', Auth::id())
                ->firstOrFail();
        } else {
            $chat = Chat::create([
                'id' => (string) Str::uuid(),
                'title' => 'Chat - ',
                'knowledge_base_id' => $knowledgeBase->id,
                'owner_id' => Auth::id(),
            ]);
        }

        ChatMessage::create([
            'chat_id' => $chat->id,
            'role'    => 'user',
            'content' => $request->message,
        ]);

        $history = $chat->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->values()
            ->toArray();

        $result = $ai->ask(
            kbId: $knowledgeBase->id,
            query: $request->message,
            history: $history,
        );

        ChatMessage::create([
            'chat_id' => $chat->id,
            'role'    => 'assistant',
            'content' => $result['answer'],
            'sources' => $result['sources'] ?? null,
            'usage'   => $result['usage'] ?? null,
        ]);

        if (!$chat->title_generated) {
            GenerateChatTitle::dispatch($chat->id)->onQueue('low');
        }

        if ($chat->messages()->limit(3)->count() == 2) {
            $chat->load([
                'messages' => fn($q) => $q->orderBy('created_at')
            ]);
            return new ChatResource($chat);
        }
        $chat->load([
            'messages' => fn($q) => $q->latest()->take(2)
        ]);

        $chat->messages = $chat->messages->sortBy('created_at')->values();

        return new ChatResource($chat);
    }

    public function update(Request $request, Chat $chat)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $chat->update([
            'title' => $request->title,
            'title_generated' => true,
        ]);

        return new ChatLiteResource($chat->fresh());
    }

    public function remove(Chat $chat)
    {
        $chat->delete();
        return response()->json(['message' => 'chat_deleted_successfully']);
    }
}
