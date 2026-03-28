<?php

namespace App\Services\Analytics\DTO;

use Spatie\LaravelData\Data;

class AnalyticsFilterDTO extends Data
{
    public function __construct(
        public ?string $period = 'month',
        public ?string $startDate = null,
        public ?string $endDate = null
    ) {}
}