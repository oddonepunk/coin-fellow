<?php

namespace App\Services\Users\DTO;

use Spatie\LaravelData\Data;

class SearchUsersDTO extends Data
{
    public function __construct(
        public string $query,
        public ?string $excludeGroupId = null,
        public int $limit = 10
    ) {}
}