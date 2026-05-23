<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Support\ExcelExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticleService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->withCount('depots')
            ->latest()
            ->paginate(100)
            ->withQueryString()
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'reference' => $article->reference,
                'name' => $article->display_name,
                'depots_count' => $article->depots_count,
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

    private function baseQuery(array $filters)
    {
        return Article::query()
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner
                ->where('reference', 'like', "%{$value}%")
                ->orWhere('name', 'like', "%{$value}%")));
    }
}
