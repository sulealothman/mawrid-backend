<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'language'            => $this->language,
            'dark_mode'           => (bool) $this->dark_mode,
            'sidebar_collapse'    => (bool) $this->sidebar_collapse,
            'email_notifications' => (bool) $this->email_notifications,
        ];
    }
}
