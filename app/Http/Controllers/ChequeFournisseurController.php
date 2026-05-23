<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChequeFournisseurRequest;
use App\Models\Bank;
use App\Models\ChequeFournisseur;
use App\Services\ChequeFournisseurService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeFournisseurController extends Controller
{
    public function index(Request $request, ChequeFournisseurService $service): Response|StreamedResponse
    {
        $filters = $request->only(['search', 'fournisseur_id', 'statut', 'banque', 'date_emission_from', 'date_emission_to', 'date_echeance_from', 'date_echeance_to']);

        if ($request->boolean('export')) {
            return $service->export($filters);
        }

        return Inertia::render('ChequeFournisseurs/Index', [
            'cheques' => $service->list($filters),
            'filters' => $filters,
            ...$service->options(),
        ]);
    }

    public function create(ChequeFournisseurService $service): Response
    {
        return Inertia::render('ChequeFournisseurs/Create', $service->options());
    }

    public function store(StoreChequeFournisseurRequest $request): RedirectResponse
    {
        $data = $this->prepareData($request);
        ChequeFournisseur::create($data);

        return redirect()->route('cheque-fournisseurs.index')->with('success', 'Chèque fournisseur créé.');
    }

    public function show(ChequeFournisseur $chequeFournisseur, ChequeFournisseurService $service): Response
    {
        $chequeFournisseur->load('fournisseur');

        return Inertia::render('ChequeFournisseurs/Show', [
            'cheque' => $service->serialize($chequeFournisseur),
        ]);
    }

    public function pdf(ChequeFournisseur $chequeFournisseur, ChequeFournisseurService $service): \Symfony\Component\HttpFoundation\Response
    {
        return $service->pdf($chequeFournisseur);
    }

    public function edit(ChequeFournisseur $chequeFournisseur, ChequeFournisseurService $service): Response
    {
        $chequeFournisseur->load('fournisseur');

        return Inertia::render('ChequeFournisseurs/Edit', [
            'cheque' => $service->serialize($chequeFournisseur),
            ...$service->options(),
        ]);
    }

    public function update(StoreChequeFournisseurRequest $request, ChequeFournisseur $chequeFournisseur): RedirectResponse
    {
        $chequeFournisseur->update($this->prepareData($request, $chequeFournisseur));

        return redirect()->route('cheque-fournisseurs.index')->with('success', 'Chèque fournisseur mis à jour.');
    }

    public function updateStatus(Request $request, ChequeFournisseur $chequeFournisseur): RedirectResponse
    {
        $data = $request->validate(['statut' => ['required', Rule::in(ChequeFournisseur::STATUSES)]]);
        $chequeFournisseur->update($data);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function destroy(ChequeFournisseur $chequeFournisseur): RedirectResponse
    {
        if ($chequeFournisseur->piece_jointe) {
            Storage::disk('public')->delete($chequeFournisseur->piece_jointe);
        }

        $chequeFournisseur->delete();

        return back()->with('success', 'Chèque fournisseur supprimé.');
    }

    private function prepareData(StoreChequeFournisseurRequest $request, ?ChequeFournisseur $cheque = null): array
    {
        $data = $request->validated();
        $bankName = trim((string) ($data['banque'] ?? ''));

        if (($data['bank_id'] ?? null) && $bankName === '') {
            $bankName = Bank::find($data['bank_id'])?->name ?? '';
        }

        $data['banque'] = $bankName ?: null;

        if ($request->hasFile('piece_jointe')) {
            if ($cheque?->piece_jointe) {
                Storage::disk('public')->delete($cheque->piece_jointe);
            }

            $data['piece_jointe'] = $request->file('piece_jointe')->store('cheques/fournisseurs', 'public');
        }

        return $data;
    }
}
