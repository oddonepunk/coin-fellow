<?php

namespace App\Services\Groups\DTO;

use Spatie\LaravelData\Data;

class MemberStatsDTO extends Data
{
    public function __construct(
        public string $groupId,
        public string $userId
    ) {}
}