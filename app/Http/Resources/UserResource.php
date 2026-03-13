<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{

    protected $token;


    public function withToken($token): self
    {
        $this->token = $token;
        return $this;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'email' => $this->email,

            'phone' => $this->phone,

            'access_token' => $this->token,

            'avatar' => $this->avatar ?
                $this->transformUrl(Storage::disk('s3')->url($this->avatar))
                : null,

            'preferences' => new UserPreferenceResource($this->whenLoaded('preferences')),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }

    private function transformUrl($url)
    {
        if (app()->environment('local')) {
            return str_replace('http://minio:9000', 'http://localhost:9000', $url);
        }

        return $url;
    }
}
