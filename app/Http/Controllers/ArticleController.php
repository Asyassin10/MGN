<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Depot;
use App\Services\ArticleService;
use App\Support\DeleteBlockers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticleController extends Controller
{
    public function index(Request $request, ArticleService $service): Response|StreamedResponse
    {
        $filters = $request->only('search');

        if ($request->boolean('export')) {
            return $service->export($filters);
        }

        return Inertia::render('Articles/Index', [
            'articles' => $service->list($filters),
            'filters' => $filters,
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $article = Article::create($request->validated());
        $sync = Depot::query()->pluck('id')->mapWithKeys(fn ($id) => [$id => ['quantity' => 1]])->all();
        $article->depots()->sync($sync);

        return back()->with('success', 'Article créé et assigné aux dépôts.');
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $article->update($request->validated());

        return back()->with('success', 'Article mis à jour.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $message = DeleteBlockers::message('cet article', [
            'lignes d’opérations' => $article->operationLines()->count(),
            'dépôts avec stock non nul' => $article->depots()->wherePivot('quantity', '!=', 0)->count(),
        ]);

        if ($message) {
            return back()->with('error', $message);
        }

        $article->depots()->detach();
        $article->delete();

        return back()->with('success', 'Article supprimé.');
    }
}
