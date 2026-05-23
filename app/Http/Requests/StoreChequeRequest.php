<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChequeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['client', 'fournisseur'])],
            'tier_value' => ['nullable', 'string'],
            'numero_cheque' => ['required', 'string', 'max:255'],
            'banque' => ['required', 'string', 'max:255'],
            'tireur_signataire' => ['nullable', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
            'date_emission' => ['nullable', 'date'],
            'date_echeance' => ['nullable', 'date'],
            'statut' => ['required', Rule::in(['en_cours', 'encaisse', 'impaye'])],
            'note' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:4096'],
        ];
    }
}
