<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustDepotStockRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'article_id' => ['required', 'exists:articles,id'],
            'adjustment_type' => ['required', Rule::in(['add', 'subtract'])],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
