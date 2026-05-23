<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFournisseurReleveCompteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code_client' => ['required', 'string', 'max:255'],
            'date_releve' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
