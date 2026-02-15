<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this['access_token'],
            'refresh_token' => $this['refresh_token'] ?? null,
            'token_type' => 'bearer',
            'expires_in' => $this['expires_in'] ?? 3600,
            'user' => new UserResource($this['user']),
        ];
    }
}