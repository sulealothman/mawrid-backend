<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public static $wrap = null;

    protected ?array $session;


    public function __construct($resource, ?array $session = null)
    {
        parent::__construct($resource);
        $this->session = $session;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'knowledge_base_id' => $this->knowledge_base_id,
            'title' => $this->title,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'messages' => ChatMessageResource::collection(
                $this->whenLoaded('messages')
            ),
            'session' => $this->session,
        ];
    }
}
