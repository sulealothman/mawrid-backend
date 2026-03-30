<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiChatService
{
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
