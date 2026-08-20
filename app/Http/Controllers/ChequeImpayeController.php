<?php

namespace App\Http\Controllers;

use App\Models\ChequeImpaye;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChequeImpayeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'type', 'statut']);

        return Inertia::render('Cheques/Impayes', [
            'cheques' => ChequeImpaye::query()
                ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner
                    ->where('numero_cheque', 'like', "%{$value}%")
                    ->orWhere('fournisseur_nom', 'like', "%{$value}%")
                    ->orWhere('tireur_signataire', 'like', "%{$value}%")))
                ->when($filters['type'] ?? null, fn ($query, $value) => $query->where('type', $value))
                ->when($filters['statut'] ?? null, fn ($query, $value) => $query->where('statut', $value))
                ->latest('id')
                ->paginate(100)
                ->withQueryString()
                ->through(fn (ChequeImpaye $cheque) => $this->serialize($cheque)),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ChequeImpaye::create([...$this->validated($request), 'statut' => 'impaye', 'date_paiement' => null]);

        return back()->with('success', 'Chèque impayé ajouté.');
    }

    public function update(Request $request, ChequeImpaye $chequeImpaye): RedirectResponse
    {
        $chequeImpaye->update($this->validated($request));

        return back()->with('success', 'Chèque impayé mis à jour.');
    }

    public function pay(Request $request, ChequeImpaye $chequeImpaye): RedirectResponse
    {
        $data = $request->validate([
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', Rule::in(['espece', 'virement'])],
        ]);
        $chequeImpaye->update([...$data, 'statut' => 'paye']);

        return back()->with('success', 'Paiement enregistré.');
    }

    public function destroy(ChequeImpaye $chequeImpaye): RedirectResponse
    {
        $chequeImpaye->delete();

        return back()->with('success', 'Chèque impayé supprimé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(ChequeImpaye::TYPES)],
            'numero_cheque' => ['required', 'string', 'max:255'],
            'fournisseur_nom' => ['required', 'string', 'max:255'],
            'tireur_signataire' => ['nullable', 'string', 'max:255'],
            'date_remise' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function serialize(ChequeImpaye $cheque): array
    {
        return [
            'id' => $cheque->id,
            'type' => $cheque->type,
            'numero_cheque' => $cheque->numero_cheque,
            'fournisseur_nom' => $cheque->fournisseur_nom,
            'tireur_signataire' => $cheque->tireur_signataire,
            'date_remise' => $cheque->date_remise?->format('Y-m-d'),
            'statut' => $cheque->statut,
            'date_paiement' => $cheque->date_paiement?->format('Y-m-d'),
            'mode_paiement' => $cheque->mode_paiement,
            'montant' => (float) $cheque->montant,
            'note' => $cheque->note,
        ];
    }
}
