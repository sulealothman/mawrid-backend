<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');
        return [
            'id'    => $this->id,
            'name'  => $this->original_name,
            'url'   => $disk->temporaryUrl(
                $this->path,
                now()->addHours(12)
            ),
            'status' => $this->latestOperation?->status?->value,
            'mime_type' => $this->mime_type,
            'created_at' => $this->created_at,
        ];
    }
}
