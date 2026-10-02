<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ArticleGroupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Groupes/Index', [
            'groups' => ArticleGroup::query()->withCount('articles')->orderBy('name')->get()->map(fn (ArticleGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'articles_count' => $group->articles_count,
                'protected' => $group->name === ArticleGroup::DEFAULT_NAME,
            ]),
        ]);
    }

    public function show(Request $request, ArticleGroup $groupe): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Groupes/Show', [
            'group' => ['id' => $groupe->id, 'name' => $groupe->name, 'protected' => $groupe->name === ArticleGroup::DEFAULT_NAME],
            'articles' => $groupe->articles()
                ->when($search, fn ($query, $value) => $query->where(fn ($inner) => $inner
                    ->where('reference', 'like', "%{$value}%")
                    ->orWhere('name', 'like', "%{$value}%")))
                ->orderBy('reference')
                ->paginate(100)
                ->withQueryString()
                ->through(fn (Article $article) => [
                    'id' => $article->id,
                    'reference' => $article->reference,
                    'name' => $article->display_name,
                    'unite' => $article->unite,
                ]),
            'available' => Article::query()->with('group')->where(fn ($query) => $query->whereNull('group_id')->orWhere('group_id', '!=', $groupe->id))
                ->orderBy('reference')->get()
                ->map(fn (Article $article) => [
                    'id' => $article->id,
                    'reference' => $article->reference,
                    'name' => $article->display_name,
                    'group_name' => $article->group?->name,
                ]),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:article_groups,name']]);
        $group = ArticleGroup::create($data);

        return redirect()->route('groupes.show', $group)->with('success', 'Groupe créé. Ajoutez maintenant ses articles.');
    }

    public function update(Request $request, ArticleGroup $groupe): RedirectResponse
    {
        if ($groupe->name === ArticleGroup::DEFAULT_NAME) {
            return back()->with('error', 'Le groupe Général ne peut pas être modifié.');
        }

        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('article_groups', 'name')->ignore($groupe)]]);
        $groupe->update($data);

        return back()->with('success', 'Groupe mis à jour.');
    }

    public function destroy(ArticleGroup $groupe): RedirectResponse
    {
        if ($groupe->name === ArticleGroup::DEFAULT_NAME) {
            return back()->with('error', 'Le groupe Général ne peut pas être supprimé.');
        }

        $groupe->articles()->update(['group_id' => ArticleGroup::general()->id]);
        $groupe->delete();

        return redirect()->route('groupes.index')->with('success', 'Groupe supprimé. Ses articles sont revenus dans Général.');
    }

    public function remove(Request $request, ArticleGroup $groupe): RedirectResponse
    {
        if ($groupe->name === ArticleGroup::DEFAULT_NAME) {
            return back()->with('error', 'Les articles du groupe Général ne peuvent pas être retirés : assignez-les à un autre groupe.');
        }

        $data = $request->validate(['selected_ids' => ['required', 'array', 'min:1'], 'selected_ids.*' => ['integer']]);
        $count = $groupe->articles()->whereKey($data['selected_ids'])->update(['group_id' => ArticleGroup::general()->id]);

        return back()->with('success', $count.' article(s) retiré(s) du groupe (retour dans Général).');
    }

    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group_id' => ['required', 'integer', 'exists:article_groups,id'],
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer'],
        ]);

        Article::whereKey($data['selected_ids'])->update(['group_id' => $data['group_id']]);

        return back()->with('success', count($data['selected_ids']).' article(s) assigné(s).');
    }
}
