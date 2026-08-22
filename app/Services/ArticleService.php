<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Depot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Support\ExcelExport;
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
                'id' => $article->id,
                'reference' => $article->reference,
                'name' => $article->display_name,
                'total_quantity' => (int) ($article->total_quantity ?? 0),
            ]);
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)
            ->withCount('depots')
            ->latest()
            ->get()
            ->map(fn (Article $article) => [
                $article->reference,
                $article->display_name,
                $article->depots_count,
            ]);

        return ExcelExport::download('articles-export', ['Code', 'Article', 'Depots assignes'], $rows);
    }

    public function show(Article $article): array
    {
        $article->load('depots');

        return [
            'article' => [
                'id' => $article->id,
                'reference' => $article->reference,
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

    private function baseQuery(array $filters)
    {
        return Article::query()
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner
                ->where('reference', 'like', "%{$value}%")
                ->orWhere('name', 'like', "%{$value}%")));
    }
}
