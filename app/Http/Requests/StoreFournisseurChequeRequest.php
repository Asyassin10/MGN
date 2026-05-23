<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\FournisseurCheque;

class StoreFournisseurChequeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fournisseur_id' => ['nullable', 'exists:fournisseurs,id'],
            'type' => ['nullable', Rule::in(FournisseurCheque::TYPES)],
            'numero_cheque' => ['required', 'string', 'max:255'],
            'banque' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
            'piece_jointe' => ['nullable', 'file', 'max:4096'],
            'motif' => ['nullable', 'string'],
            'tireur_signataire' => ['nullable', 'string', 'max:255'],
            'date_emission' => ['nullable', 'date'],
            'date_echeance' => ['nullable', 'date'],
            'statut' => ['required', Rule::in(FournisseurCheque::STATUSES)],
            'note' => ['nullable', 'string'],
        ];
    }
}
