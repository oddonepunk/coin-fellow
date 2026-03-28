<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChartDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }
        
        return [
            'labels' => $this->resource['labels'] ?? [],
            'datasets' => $this->resource['datasets'] ?? [],
        ];
    }
}