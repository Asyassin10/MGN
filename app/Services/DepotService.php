<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Depot;
use App\Support\ExcelExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepotService
{
    public function list(): array
    {
        return Depot::query()
            ->with('articles')
            ->latest()
            ->get()
            ->map(fn (Depot $depot) => [
                'id' => $depot->id,
                'name' => $depot->name,
                'location' => $depot->location,
                'total_stock' => (int) $depot->articles->sum('pivot.quantity'),
                'articles_count' => $depot->articles->count(),
            ])
            ->all();
    }

    public function show(Depot $depot, ?string $search): array
    {
        $depot->load('articles');

        return [
            'depot' => [
                'id' => $depot->id,
                'name' => $depot->name,
                'location' => $depot->location,
                'total_stock' => (int) $depot->articles->sum('pivot.quantity'),
                'articles_count' => $depot->articles->count(),
            ],
            'articles' => $depot->articles()
                ->when($search, fn ($query, $value) => $query->where(fn ($inner) => $inner
                    ->where('articles.name', 'like', "%{$value}%")
                    ->orWhere('articles.reference', 'like', "%{$value}%")))
                ->orderByDesc('depot_article.created_at')
                ->orderByDesc('articles.id')
                ->paginate(100)
                ->withQueryString()
                ->through(fn (Article $article) => [
                    'id' => $article->id,
                    'name' => $article->display_name,
                    'reference' => $article->reference,
                    'quantity' => (int) $article->pivot->quantity,
                ]),
            'articleOptions' => Article::query()->orderBy('name')->get(['id', 'name', 'reference'])->map(fn ($article) => [
                'value' => (string) $article->id,
                'label' => "{$article->reference} - {$article->display_name}",
            ]),
        ];
    }

    public function exportDepots(): StreamedResponse
    {
        $rows = collect($this->list())
            ->map(fn (array $depot) => [
                $depot['name'],
                $depot['location'],
                $depot['total_stock'],
                $depot['articles_count'],
            ]);

        return ExcelExport::download('depots-export', ['Depot', 'Emplacement', 'Stock total', 'Articles'], $rows);
    }

    public function exportArticles(Depot $depot, ?string $search): StreamedResponse
    {
        $rows = $depot->articles()
            ->when($search, fn ($query, $value) => $query->where(fn ($inner) => $inner
                ->where('articles.name', 'like', "%{$value}%")
                ->orWhere('articles.reference', 'like', "%{$value}%")))
            ->orderByDesc('depot_article.created_at')
            ->orderByDesc('articles.id')
            ->get()
            ->map(fn (Article $article) => [
                $article->reference,
                $article->display_name,
                (int) $article->pivot->quantity,
            ]);

        return ExcelExport::download('depot-'.$depot->id.'-articles-export', ['Reference', 'Article', 'Quantite'], $rows);
    }
}
