<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleGroup;
use App\Models\Depot;
use App\Support\ExcelExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticleService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->withSum('depots as total_quantity', 'depot_article.quantity')
            ->latest()
            ->paginate(100)
            ->withQueryString()
            ->through(fn (Article $article) => [
                ...$this->fields($article),
                'name' => $article->display_name,
                'total_quantity' => (int) ($article->total_quantity ?? 0),
            ]);
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)
            ->when($filters['selected_ids'] ?? [], fn ($query, array $ids) => $query->whereKey($ids))
            ->withCount('depots')
            ->latest()
            ->get()
            ->map(fn (Article $article) => [
                $article->reference,
                $article->display_name,
                $article->group?->name,
                $article->unite,
                $article->prix_achat_ht,
                $article->prix_detail_ht,
                $article->prix_gros_ht,
                $article->depots_count,
            ]);

        return ExcelExport::download('articles-export', ['Code', 'Article', 'Groupe', 'Unite', 'Prix achat HT', 'Prix detail HT', 'Prix gros HT', 'Depots assignes'], $rows);
    }

    public function show(Article $article): array
    {
        $article->load('depots');

        return [
            'article' => [
                ...$this->fields($article),
                'name' => $article->display_name,
                'total_quantity' => (int) $article->depots->sum('pivot.quantity'),
            ],
            'depots' => $article->depots
                ->sortBy('name')
                ->values()
                ->map(fn (Depot $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'location' => $item->location,
                    'quantity' => (int) $item->pivot->quantity,
                ]),
            'operations' => $article->operationLines()
                ->with(['operation.depot', 'operation.employee'])
                ->latest('id')
                ->paginate(100)
                ->withQueryString()
                ->through(fn ($line) => [
                    'id' => $line->id,
                    'operation_id' => $line->operation_id,
                    'reference' => $line->operation?->reference,
                    'type' => $line->operation?->type,
                    'depot' => $line->operation?->depot?->name,
                    'employee' => $line->operation?->employee?->name,
                    'quantity' => $line->quantity,
                    'note' => $line->operation?->note,
                    'created_at' => $line->operation?->created_at?->format('Y-m-d H:i'),
                ]),
        ];
    }

    public function groupOptions(): array
    {
        return ArticleGroup::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (ArticleGroup $group) => ['value' => (string) $group->id, 'label' => $group->name])
            ->all();
    }

    private function fields(Article $article): array
    {
        $data = [
            'id' => $article->id,
            'reference' => $article->reference,
            'group_id' => (string) ($article->group_id ?? ''),
            'group_name' => $article->group?->name,
            'nom_fournisseur' => $article->nom_fournisseur,
            'unite' => $article->unite,
        ];

        foreach (Article::PRICE_FIELDS as $field) {
            $data[$field] = (float) $article->{$field};
        }

        return $data;
    }

    private function baseQuery(array $filters)
    {
        return Article::query()
            ->with('group')
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner
                ->where('reference', 'like', "%{$value}%")
                ->orWhere('name', 'like', "%{$value}%")))
            ->when($filters['group_id'] ?? null, fn ($query, $value) => $query->where('group_id', $value));
    }
}
