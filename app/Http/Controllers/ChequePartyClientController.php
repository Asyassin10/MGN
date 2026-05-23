<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChequePartyClientRequest;
use App\Models\ChequePartyClient;
use App\Support\ExcelExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequePartyClientController extends Controller
{
    public function index(Request $request): Response|StreamedResponse
    {
        $filters = $request->only(['search']);
        $query = ChequePartyClient::query()
            ->withCount('cheques')
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner->where('nom', 'like', "%{$value}%")->orWhere('telephone', 'like', "%{$value}%")->orWhere('email', 'like', "%{$value}%")));

        if ($request->boolean('export')) {
            return ExcelExport::download('cheque-party-clients-export', ['Nom', 'Telephone', 'Email', 'Cheques'], $query->latest()->get()->map(fn (ChequePartyClient $party) => [
                $party->nom,
                $party->telephone,
                $party->email,
                $party->cheques_count,
            ]));
        }

        return Inertia::render('ChequeParties/Index', [
            'title' => 'Clients chèques',
            'routeName' => 'cheque-party-clients',
            'parties' => $query
                ->latest()
                ->paginate(100)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreChequePartyClientRequest $request): RedirectResponse
    {
        ChequePartyClient::create($request->validated());

        return back()->with('success', 'Client chèque créé.');
    }

    public function update(StoreChequePartyClientRequest $request, ChequePartyClient $chequePartyClient): RedirectResponse
    {
        $chequePartyClient->update($request->validated());

        return back()->with('success', 'Client chèque mis à jour.');
    }

    public function destroy(ChequePartyClient $chequePartyClient): RedirectResponse
    {
        if ($chequePartyClient->cheques()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce client : des chèques lui sont associés.');
        }

        $chequePartyClient->delete();

        return back()->with('success', 'Client chèque supprimé.');
    }
}
