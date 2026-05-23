<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFournisseurPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date_paiement' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:0'],
            'numero_cheque' => ['required', 'string', 'max:255'],
            'banque' => ['required', 'string', 'max:255'],
            'date_echeance' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
