<?php

namespace App\Services\Categories\DTO;

use Spatie\LaravelData\Data;

class CategoryDTO extends Data
{
    public function __construct(
        public string $name,
        public ?string $icon = null,
        public ?string $color = null,
        public ?int $userId = null
    ) {}
}