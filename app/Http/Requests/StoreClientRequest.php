<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'ville' => ['nullable', 'string', 'max:120'],
            'price_type' => ['nullable', Rule::in(['detail', 'demi_gros', 'gros', 'special'])],
            'note' => ['nullable', 'string'],
        ];
    }
}
