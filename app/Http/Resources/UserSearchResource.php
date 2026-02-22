<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'full_name' => $this->full_name,
            'initials' => $this->initials,
            'display_name' => $this->getDisplayName()
        ];
    }

    private function getDisplayName(): string
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        if ($this->first_name) {
            return $this->first_name;
        }
        if ($this->username) {
            return '@' . $this->username;
        }
        return $this->email;
    }
}