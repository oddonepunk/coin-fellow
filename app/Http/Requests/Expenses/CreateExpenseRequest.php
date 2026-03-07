<?php

namespace App\Http\Requests\Expenses;

use App\Http\Requests\BaseRequest;

class CreateExpenseRequest extends BaseRequest 
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payer_id' => 'nullable|exists:users,id', 
            'categoryId' => 'nullable|exists:categories,id',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:users,id',
        ];
    }
}