<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustDepotStockRequest;
use App\Http\Requests\StoreDepotRequest;
use App\Http\Requests\UpdateDepotRequest;
use App\Models\Article;
use App\Models\Depot;
use App\Services\DepotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepotController extends Controller
{
    public function index(Request $request, DepotService $service): Response|StreamedResponse
    {
        if ($request->boolean('export')) {
            return $service->exportDepots();
        }

        return Inertia::render('Depots/Index', [
            'depots' => $service->list(),
        ]);
    }

    public function store(StoreDepotRequest $request): RedirectResponse
    {
        $depot = Depot::create($request->validated());

        return redirect()->route('depots.show', $depot)->with('success', 'Dépôt créé.');
    }

    public function show(Request $request, Depot $depot, DepotService $service): Response|StreamedResponse
    {
        if ($request->boolean('export')) {
            return $service->exportArticles($depot, $request->string('search')->toString() ?: null);
        }

        return Inertia::render('Depots/Show', [
            ...$service->show($depot, $request->string('search')->toString()),
            'filters' => $request->only('search'),
        ]);
    }

    public function update(UpdateDepotRequest $request, Depot $depot): RedirectResponse
    {
        $depot->update($request->validated());

        return back()->with('success', 'Dépôt mis à jour.');
    }

    public function destroy(Depot $depot): RedirectResponse
    {
        if ($depot->operations()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce dépôt : des opérations y sont enregistrées.');
        }

        if ($depot->articles()->wherePivot('quantity', '!=', 0)->exists()) {
            return back()->with('error', 'Impossible de supprimer ce dépôt : son stock doit être nul.');
        }

        $depot->articles()->detach();
        $depot->delete();

        return redirect()->route('depots.index')->with('success', 'Dépôt supprimé.');
    }

    public function adjustStock(AdjustDepotStockRequest $request, Depot $depot): RedirectResponse
    {
        $validated = $request->validated();
        $article = Article::findOrFail($validated['article_id']);
        $current = (int) ($depot->articles()->where('article_id', $article->id)->first()?->pivot->quantity ?? 0);

        if ($validated['adjustment_type'] === 'subtract' && $current < $validated['quantity']) {
            return back()->with('error', 'Stock insuffisant.');
        }

        $newQuantity = $validated['adjustment_type'] === 'add'
            ? $current + $validated['quantity']
            : $current - $validated['quantity'];

        $depot->articles()->syncWithoutDetaching([$article->id => ['quantity' => $newQuantity]]);

        return back()->with('success', 'Stock mis à jour.');
    }
}
