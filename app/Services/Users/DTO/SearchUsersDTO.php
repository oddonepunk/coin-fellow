<?php

namespace App\Services\Users\DTO;

use Spatie\LaravelData\Data;

class SearchUsersDTO extends Data
{
    public function __construct(
        public string $query,
        public int $limit = 10
    ) {}
}