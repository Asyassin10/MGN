<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Support\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'fournisseur', 'type', 'statut', 'sortie']);

        return Inertia::render('Cheques/Index', [
            'cheques' => $this->filteredQuery($filters)
                ->latest('id')
                ->paginate(100)
                ->withQueryString()
                ->through(fn (Cheque $cheque) => $this->serialize($cheque)),
            'filters' => $filters,
            'montantDisponible' => (float) Cheque::query()->where('est_sorti', false)->sum('montant'),
            'chequesDisponiblesCount' => Cheque::query()->where('est_sorti', false)->count(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'fournisseur', 'type', 'statut', 'sortie']);
        $selectedIds = $request->validate([
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer', 'distinct', 'exists:cheques,id'],
        ])['selected_ids'] ?? [];

        $rows = ($selectedIds !== [] ? Cheque::query() : $this->filteredQuery($filters))
            ->when($selectedIds !== [], fn (Builder $query) => $query->whereKey($selectedIds))
            ->latest('id')
            ->get();

        return ExcelExport::download('cheques-export', [
            'N° chèque', 'Type', 'Client', 'Tireur / signataire', 'Montant', 'Émission', 'Échéance', 'Statut', 'Sorti', 'Fournisseur', 'Note',
        ], $rows->map(fn (Cheque $cheque) => [
            $cheque->numero_cheque,
            $cheque->type === 'cheque' ? 'Chèque' : 'Effet',
            $cheque->client_nom,
            $cheque->tireur_signataire,
            $cheque->montant,
            $cheque->date_emission?->format('Y-m-d'),
            $cheque->date_echeance?->format('Y-m-d'),
            $cheque->statut,
            $cheque->est_sorti ? 'Oui' : 'Non',
            $cheque->fournisseur_sortie_nom,
            $cheque->note,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        Cheque::create($this->validated($request));

        return back()->with('success', 'Chèque ajouté.');
    }

    public function update(Request $request, Cheque $cheque): RedirectResponse
    {
        $cheque->update($this->validated($request));

        return back()->with('success', 'Chèque mis à jour.');
    }

    public function updateInline(Request $request, Cheque $cheque): RedirectResponse
    {
        $cheque->update($request->validate([
            'statut' => ['sometimes', 'required', Rule::in(Cheque::STATUSES)],
        ]));

        return back()->with('success', 'Chèque mis à jour.');
    }

    public function updateSortie(Request $request, Cheque $cheque): RedirectResponse
    {
        $data = $request->validate([
            'est_sorti' => ['required', 'boolean'],
            'fournisseur_sortie_nom' => [Rule::requiredIf($request->boolean('est_sorti')), 'nullable', 'string', 'max:255'],
        ]);

        $cheque->update([
            'est_sorti' => $data['est_sorti'],
            'fournisseur_sortie_nom' => $data['est_sorti'] ? $data['fournisseur_sortie_nom'] : null,
        ]);

        return back()->with('success', 'Sortie du chèque mise à jour.');
    }

    public function destroy(Cheque $cheque): RedirectResponse
    {
        $cheque->delete();

        return back()->with('success', 'Chèque supprimé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(Cheque::TYPES)],
            'numero_cheque' => ['required', 'string', 'max:255'],
            'client_nom' => ['required', 'string', 'max:255'],
            'tireur_signataire' => ['nullable', 'string', 'max:255'],
            'date_emission' => ['required', 'date'],
            'date_echeance' => ['required', 'date', 'after_or_equal:date_emission'],
            'statut' => ['required', Rule::in(Cheque::STATUSES)],
            'montant' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function filteredQuery(array $filters): Builder
    {
        return Cheque::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                ->where('numero_cheque', 'like', "%{$value}%")
                ->orWhere('client_nom', 'like', "%{$value}%")
                ->orWhere('fournisseur_sortie_nom', 'like', "%{$value}%")
                ->orWhere('tireur_signataire', 'like', "%{$value}%")))
            ->when($filters['fournisseur'] ?? null, fn (Builder $query, string $value) => $query->where('fournisseur_sortie_nom', 'like', "%{$value}%"))
            ->when($filters['type'] ?? null, fn (Builder $query, string $value) => $query->where('type', $value))
            ->when($filters['statut'] ?? null, fn (Builder $query, string $value) => $query->where('statut', $value))
            ->when(isset($filters['sortie']) && $filters['sortie'] !== '', fn (Builder $query) => $query->where('est_sorti', $filters['sortie'] === '1'));
    }

    private function serialize(Cheque $cheque): array
    {
        return [
            'id' => $cheque->id,
            'type' => $cheque->type,
            'numero_cheque' => $cheque->numero_cheque,
            'client_nom' => $cheque->client_nom,
            'tireur_signataire' => $cheque->tireur_signataire,
            'date_emission' => $cheque->date_emission?->format('Y-m-d'),
            'date_echeance' => $cheque->date_echeance?->format('Y-m-d'),
            'statut' => $cheque->statut,
            'est_sorti' => $cheque->est_sorti,
            'fournisseur_sortie_nom' => $cheque->fournisseur_sortie_nom,
            'montant' => (float) $cheque->montant,
            'note' => $cheque->note,
        ];
    }
}
