<?php

namespace App\Http\Requests\Analytics;

use App\Http\Requests\BaseRequest;

class AnalyticsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => 'nullable|in:week,month,year,all',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}