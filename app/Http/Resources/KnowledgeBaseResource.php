<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Chat\ChatLiteResource;

class KnowledgeBaseResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,

            'files_count' => $this->whenCounted('files'),
            'files' => FileResource::collection(
                $this->whenLoaded('files')
            ),
            'chats_count' => $this->whenCounted('chats'),
            'chats' => ChatLiteResource::collection(
                $this->whenLoaded('chats')
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
