<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function rules(): array
    {
        $article = $this->route('article');

        return [
            'reference' => ['required', 'string', 'max:50', Rule::unique('articles', 'reference')->ignore($article)],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
