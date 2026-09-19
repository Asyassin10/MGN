<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCaisseEntryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['entree', 'sortie'])],
            'party' => ['required', 'string', 'regex:/^(client|fournisseur):\d+$/'],
            'montant' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('party')) {
                return;
            }

            [$partyType, $partyId] = explode(':', (string) $this->input('party'));

            if ($partyType === 'client' && ! \App\Models\Client::query()->whereKey($partyId)->exists()) {
                $validator->errors()->add('party', 'Client introuvable.');
            }

            if ($partyType === 'fournisseur' && ! \App\Models\Fournisseur::query()->whereKey($partyId)->exists()) {
                $validator->errors()->add('party', 'Fournisseur introuvable.');
            }
        });
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();
        [$partyType, $partyId] = explode(':', $data['party']);
        unset($data['party']);
        $data['client_id'] = $partyType === 'client' ? (int) $partyId : null;
        $data['fournisseur_id'] = $partyType === 'fournisseur' ? (int) $partyId : null;

        return $data;
    }
}
