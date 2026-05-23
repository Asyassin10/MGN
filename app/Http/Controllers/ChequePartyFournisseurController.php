<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChequePartyFournisseurRequest;
use App\Models\ChequePartyFournisseur;
use App\Support\ExcelExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequePartyFournisseurController extends Controller
{
    public function index(Request $request): Response|StreamedResponse
    {
        $filters = $request->only(['search']);
        $query = ChequePartyFournisseur::query()
            ->withCount('cheques')
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner->where('nom', 'like', "%{$value}%")->orWhere('telephone', 'like', "%{$value}%")->orWhere('email', 'like', "%{$value}%")));

        if ($request->boolean('export')) {
            return ExcelExport::download('cheque-party-fournisseurs-export', ['Nom', 'Telephone', 'Email', 'Cheques'], $query->latest()->get()->map(fn (ChequePartyFournisseur $party) => [
                $party->nom,
                $party->telephone,
                $party->email,
                $party->cheques_count,
            ]));
        }

        return Inertia::render('ChequeParties/Index', [
            'title' => 'Fournisseurs chèques',
            'routeName' => 'cheque-party-fournisseurs',
            'parties' => $query
                ->latest()
                ->paginate(100)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreChequePartyFournisseurRequest $request): RedirectResponse
    {
        ChequePartyFournisseur::create($request->validated());

        return back()->with('success', 'Fournisseur chèque créé.');
    }

    public function update(StoreChequePartyFournisseurRequest $request, ChequePartyFournisseur $chequePartyFournisseur): RedirectResponse
    {
        $chequePartyFournisseur->update($request->validated());

        return back()->with('success', 'Fournisseur chèque mis à jour.');
    }

    public function destroy(ChequePartyFournisseur $chequePartyFournisseur): RedirectResponse
    {
        if ($chequePartyFournisseur->cheques()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce fournisseur : des chèques lui sont associés.');
        }

        $chequePartyFournisseur->delete();

        return back()->with('success', 'Fournisseur chèque supprimé.');
    }
}
