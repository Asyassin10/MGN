<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFournisseurFactureRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'numero_facture' => ['required', 'string', 'max:255'],
            'date_facture' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}
