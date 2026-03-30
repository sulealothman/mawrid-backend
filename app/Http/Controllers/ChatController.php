<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Jobs\GenerateChatTitle;
use App\Http\Resources\Chat\ChatResource;
use App\Http\Resources\Chat\ChatLiteResource;
use Illuminate\Support\Facades\DB;


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

        return new ChatResource($chat, $this->buildChatSessionPayload($chat));
    }

    public function store(KnowledgeBase $knowledgeBase)
    {
        abort_if($knowledgeBase->owner_id !== Auth::id(), 403);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'title' => 'Chat - ',
            'knowledge_base_id' => $knowledgeBase->id,
            'owner_id' => Auth::id(),
        ]);

        return new ChatResource($chat, $this->buildChatSessionPayload($chat));
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

    private function buildChatSessionPayload(Chat $chat): array
    {
        return [
            'chat_id' => $chat->id,
            'ws_url' => config('services.ai.ws_url'),
            'token' => $this->issueWebSocketToken(
                userId: Auth::id(),
                chatId: $chat->id,
                knowledgeBaseId: $chat->knowledge_base_id
            ),
            'expires_in' => $this->webSocketTokenTtl(),
        ];
    }

    public function finalize(Request $request)
    {
        abort_unless(
            hash_equals(
                (string) config('services.ai.internal_key'),
                (string) $request->header('X-Internal-Key')
            ),
            403
        );

        $data = $request->validate([
            'chat_id' => 'required|uuid',
            'message' => 'required|string',
            'answer' => 'nullable|string',
            'status' => 'required|in:completed,cancelled,failed',
            'status_message' => 'nullable|string',
            'sources' => 'nullable|array',
            'usage' => 'nullable|array',
        ]);

        $chat = Chat::findOrFail($data['chat_id']);

        [$userMessage, $replyMessage] = DB::transaction(function () use ($chat, $data) {
            $userMessage = $this->createChatMessage(
                $chat,
                null,
                $data
            );

            $replyMessage = $this->createChatMessage($chat, $userMessage->id, $data);
            return [$userMessage, $replyMessage];
        });

        if (!$chat->title_generated) {
            GenerateChatTitle::dispatch($chat->id)->onQueue('low');
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'chat_id' => $chat->id,
                'user_message' => $userMessage,
                'reply_message' => $replyMessage,
            ],
        ]);
    }

    private function createChatMessage(Chat $chat, ?int $parentId, array $data): ChatMessage
    {
        return ChatMessage::create([
            'chat_id' => $chat->id,
            'parent_id' => $parentId ?? null,
            'role' => $parentId ? ($data['status'] === 'completed' ? 'assistant' : 'system') : 'user',
            'content' => $parentId ? $data['answer'] ?? '' : $data['message'],
            'status' => $data['status'],
            'status_message' => $data['status_message'] ?? null,
            'sources' => $data['sources'] ?? null,
            'usage' => $data['usage'] ?? null,
        ]);
    }

    private function issueWebSocketToken(int $userId, string $chatId, string $knowledgeBaseId): string
    {
        $now = now()->timestamp;
        $exp = $now + $this->webSocketTokenTtl();

        $payload = [
            'sub' => (string) $userId,
            'chat_id' => $chatId,
            'kb_id' => $knowledgeBaseId,
            'iat' => $now,
            'exp' => $exp,
            'jti' => (string) Str::uuid(),
        ];

        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac(
            'sha256',
            $payloadEncoded,
            (string) config('services.ai.ws_token_secret'),
            true
        );

        return $payloadEncoded . '.' . $this->base64UrlEncode($signature);
    }

    private function webSocketTokenTtl(): int
    {
        return (int) config('services.ai.ws_token_ttl', 120);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}