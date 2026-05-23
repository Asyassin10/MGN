<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientEntryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date_entree' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}
