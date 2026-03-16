<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiChatService
{
    public function ask(string $kbId, string $query, array $history = [])
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'X-Internal-Key' => config('services.ai.internal_key'),
            ])
            ->post(config('services.ai.url') . '/v1/chat', [
                'request_id' => (string) Str::uuid(),
                'kb_id' => $kbId,
                'query' => $query,
                'messages' => $history,
            ]);

        if (!$response->successful()) {
            throw new \Exception('AI Service Error: ' . $response->body());
        }

        return $response->json();
    }

    public function generateTitle(array $messages): array
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'X-Internal-Key' => config('services.ai.internal_key'),
            ])
            ->post(config('services.ai.url') . '/v1/generate-title', [
                'request_id' => (string) Str::uuid(),
                'messages' => $messages,
                'generation' => [
                    'temperature' => 0.1,
                    'max_tokens' => 20,
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('AI Title Service Error: ' . $response->body());
        }

        return $response->json();
    }
}
