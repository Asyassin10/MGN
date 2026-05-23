<?php

namespace App\Http\Requests;

use App\Models\ChequeClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChequeClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(ChequeClient::TYPES)],
            'numero_cheque' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'exists:cheque_party_clients,id'],
            'bank_id' => ['nullable', 'exists:banks,id'],
            'montant' => ['required', 'numeric', 'min:0'],
            'banque' => ['nullable', 'string', 'max:255'],
            'piece_jointe' => ['nullable', 'file', 'max:4096'],
            'motif' => ['nullable', 'string'],
            'tireur_signataire' => ['nullable', 'string', 'max:255'],
            'date_emission' => ['nullable', 'date'],
            'date_echeance' => ['nullable', 'date'],
            'statut' => ['required', Rule::in(ChequeClient::STATUSES)],
        ];
    }
}
