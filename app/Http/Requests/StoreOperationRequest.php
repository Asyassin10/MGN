<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['entree', 'sortie'])],
            'depot_id' => ['required', 'exists:depots,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'note' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.article_id' => ['required', 'exists:articles,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
