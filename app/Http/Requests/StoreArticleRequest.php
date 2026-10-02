<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function rules(): array
    {
        $article = $this->route('article');

        $rules = [
            'reference' => ['required', 'string', 'max:50', Rule::unique('articles', 'reference')->ignore($article)],
            'name' => ['required', 'string', 'max:255'],
            'group_id' => ['nullable', 'integer', 'exists:article_groups,id'],
            'nom_fournisseur' => ['nullable', 'string', 'max:255'],
            'unite' => ['nullable', Rule::in(Article::UNITS)],
        ];

        foreach (Article::PRICE_FIELDS as $field) {
            $rules[$field] = ['nullable', 'numeric', 'min:0', 'max:9999999999'];
        }

        return $rules;
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['unite'] = $data['unite'] ?? 'U';
        foreach (Article::PRICE_FIELDS as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        if (empty($data['group_id'])) {
            $data['group_id'] = \App\Models\ArticleGroup::general()->id;
        }

        return $data;
    }
}
