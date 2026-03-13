<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Services\AiChatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateChatTitle implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */

    private string $chatId;

    public function __construct(string $chatId)
    {
        $this->chatId = $chatId;
    }

    /**
     * Execute the job.
     */
    public function handle(AiChatService $aiChatService): void
    {
        $chat = Chat::find($this->chatId);

        if (!$chat || $chat->title_generated) {
            return;
        }

        if ($chat->messages()->count() > 10) {
            $chat->update([
                'title_generated' => true,
            ]);
            return;
        }

        $messages = $chat->messages()
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->toArray();

        if (empty($messages)) {
            return;
        }

        try {
            $result = $aiChatService->generateTitle($messages);

            $title = trim($result['title'] ?? '');

            if (!empty($title)) {
                $chat->update([
                    'title' => $title,
                    'title_generated' => true,
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
