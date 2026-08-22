<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Services\ArticleService;
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

    public function show(Article $article, ArticleService $service): Response
    {
        return Inertia::render('Articles/Show', $service->show($article));
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        Article::create($request->validated());

        return back()->with('success', 'Article créé. Ajoutez-le à un dépôt lors du premier ajustement de stock ou de la première opération.');
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $article->update($request->validated());

        return back()->with('success', 'Article mis à jour.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->depots()->detach();
        $article->delete();

        return back()->with('success', 'Article supprimé et retiré de tous les dépôts.');
    }
}
