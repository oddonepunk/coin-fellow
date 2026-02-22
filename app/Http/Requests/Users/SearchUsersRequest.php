<?php

namespace App\Http\Requests\Users;

use App\Http\Requests\BaseRequest;

class SearchUsersRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => 'required|string|min:2|max:255',
            'group_id' => 'nullable|string|exists:groups,id',
            'limit' => 'nullable|integer|min:1|max:50'
        ];
    }
}