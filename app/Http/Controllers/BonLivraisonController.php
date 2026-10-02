<?php

namespace App\Http\Controllers;

use App\Models\BonLivraison;
use App\Models\BonLivraisonLine;
use App\Services\BonLivraisonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BonLivraisonController extends Controller
{
    public function index(Request $request, BonLivraisonService $service): Response
    {
        $filters = $request->only(['search']);

        return Inertia::render('Livraisons/Index', [
            'bons' => $service->list($filters),
            'filters' => $filters,
        ]);
    }

    public function create(BonLivraisonService $service): Response
    {
        return Inertia::render('Livraisons/Create', $service->options());
    }

    public function store(Request $request, BonLivraisonService $service): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'client_nom' => ['nullable', 'string', 'max:255'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'mode_paiement' => ['required', Rule::in(array_keys(BonLivraison::PAYMENT_MODES))],
            'note' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.article_id' => ['required', 'integer', 'exists:articles,id'],
            'lines.*.depot_id' => ['required', 'integer', 'exists:depots,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.price_type' => ['required', Rule::in(array_keys(BonLivraisonLine::PRICE_TYPES))],
            'lines.*.prix' => ['required', 'numeric', 'min:0'],
        ]);

        $bon = $service->create($data, $request->user());

        return redirect()->route('livraisons.index')->with('success', 'Bon de livraison '.$bon->reference.' enregistré et stock mis à jour.');
    }

    public function pdf(BonLivraison $livraison, BonLivraisonService $service): HttpResponse
    {
        return $service->pdf($livraison);
    }

    public function destroy(BonLivraison $livraison, BonLivraisonService $service): RedirectResponse
    {
        $service->delete($livraison);

        return back()->with('success', 'Bon de livraison supprimé et stock rétabli.');
    }
}
