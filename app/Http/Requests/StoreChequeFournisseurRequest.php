<?php

namespace App\Http\Requests;

use App\Models\ChequeFournisseur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChequeFournisseurRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(ChequeFournisseur::TYPES)],
            'numero_cheque' => ['required', 'string', 'max:255'],
            'fournisseur_id' => ['required', 'exists:cheque_party_fournisseurs,id'],
            'bank_id' => ['nullable', 'exists:banks,id'],
            'montant' => ['required', 'numeric', 'min:0'],
            'banque' => ['nullable', 'string', 'max:255'],
            'piece_jointe' => ['nullable', 'file', 'max:4096'],
            'motif' => ['nullable', 'string'],
            'tireur_signataire' => ['nullable', 'string', 'max:255'],
            'date_emission' => ['nullable', 'date'],
            'date_echeance' => ['nullable', 'date'],
            'statut' => ['required', Rule::in(ChequeFournisseur::STATUSES)],
        ];
    }
}
