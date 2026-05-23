<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChequePartyClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
