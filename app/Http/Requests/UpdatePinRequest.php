<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_pin' => ['required', 'digits:6'],
            'pin' => ['required', 'digits:6', 'confirmed', 'different:current_pin'],
        ];
    }
}
