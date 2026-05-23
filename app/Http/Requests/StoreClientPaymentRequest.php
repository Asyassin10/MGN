<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date_paiement' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:0'],
            'mode' => ['required', Rule::in(['espece', 'cheque', 'virement'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];
    }
}
