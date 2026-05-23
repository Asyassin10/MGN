<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'ville' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string'],
        ];
    }
}
