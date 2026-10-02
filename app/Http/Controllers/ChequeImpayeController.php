<?php

namespace App\Http\Controllers;

use App\Models\ChequeImpaye;
use App\Support\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeImpayeController extends Controller
{
    public function index(Request $request): Response|StreamedResponse
    {
        $filters = $request->only(['search', 'type', 'statut']);

        if ($request->boolean('export')) {
            return $this->export($filters, $request->validate([
                'selected_ids' => ['nullable', 'array'],
                'selected_ids.*' => ['integer', 'distinct'],
            ])['selected_ids'] ?? []);
        }

        return Inertia::render('Cheques/Impayes', [
            'cheques' => $this->filteredQuery($filters)
                ->latest('id')
                ->paginate(100)
                ->withQueryString()
                ->through(fn (ChequeImpaye $cheque) => $this->serialize($cheque)),
            'filters' => $filters,
            'impayesCount' => ChequeImpaye::query()->where('statut', 'impaye')->count(),
            'impayesMontantTotal' => (float) ChequeImpaye::query()->where('statut', 'impaye')->sum('montant'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ChequeImpaye::create([...$this->validated($request), 'statut' => 'impaye', 'date_paiement' => null]);

        return back()->with('success', 'Chèque impayé ajouté.');
    }

    public function update(Request $request, ChequeImpaye $chequeImpaye): RedirectResponse
    {
        $data = $this->validated($request, true);
        $isPaid = $data['statut'] === 'paye';

        $chequeImpaye->update([
            ...$data,
            'date_paiement' => $isPaid ? $data['date_paiement'] : null,
            'mode_paiement' => $isPaid ? $data['mode_paiement'] : null,
        ]);

        return back()->with('success', 'Chèque impayé mis à jour.');
    }

    public function pay(Request $request, ChequeImpaye $chequeImpaye): RedirectResponse
    {
        $data = $request->validate([
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', Rule::in(['espece', 'virement', 'cheque'])],
        ]);
        $chequeImpaye->update([...$data, 'statut' => 'paye']);

        return back()->with('success', 'Paiement enregistré.');
    }

    public function destroy(ChequeImpaye $chequeImpaye): RedirectResponse
    {
        $chequeImpaye->delete();

        return back()->with('success', 'Chèque impayé supprimé.');
    }

    public function destroySelected(Request $request): RedirectResponse
    {
        $data = $request->validate(['selected_ids' => ['required', 'array', 'min:1'], 'selected_ids.*' => ['integer']]);
        $count = ChequeImpaye::whereKey($data['selected_ids'])->delete();

        return back()->with('success', $count.' chèque(s) impayé(s) supprimé(s).');
    }

    private function export(array $filters, array $selectedIds): StreamedResponse
    {
        $modes = ['espece' => 'Espèce', 'virement' => 'Virement', 'cheque' => 'Chèque'];

        $rows = $this->filteredQuery($filters)
            ->when($selectedIds, fn (Builder $query, array $ids) => $query->whereKey($ids))
            ->latest('id')
            ->get()
            ->map(fn (ChequeImpaye $cheque) => [
                $cheque->numero_cheque,
                ucfirst($cheque->type),
                $cheque->fournisseur_nom,
                $cheque->client_nom,
                $cheque->tireur_signataire,
                $cheque->date_remise?->format('Y-m-d'),
                $cheque->montant,
                $cheque->statut === 'paye' ? 'Paye' : 'Impaye',
                $cheque->date_paiement?->format('Y-m-d'),
                $modes[$cheque->mode_paiement] ?? '',
                $cheque->note,
            ]);

        return ExcelExport::download('cheques-impayes-export', ['N cheque', 'Type', 'Fournisseur', 'Client', 'Tireur / signataire', 'Date remise', 'Montant', 'Statut', 'Date paiement', 'Mode paiement', 'Note'], $rows);
    }

    private function filteredQuery(array $filters): Builder
    {
        return ChequeImpaye::query()
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner
                ->where('numero_cheque', 'like', "%{$value}%")
                ->orWhere('fournisseur_nom', 'like', "%{$value}%")
                ->orWhere('client_nom', 'like', "%{$value}%")
                ->orWhere('tireur_signataire', 'like', "%{$value}%")))
            ->when($filters['type'] ?? null, fn ($query, $value) => $query->where('type', $value))
            ->when($filters['statut'] ?? null, fn ($query, $value) => $query->where('statut', $value));
    }

    private function validated(Request $request, bool $withPayment = false): array
    {
        $rules = [
            'type' => ['required', Rule::in(ChequeImpaye::TYPES)],
            'numero_cheque' => ['required', 'string', 'max:255'],
            'fournisseur_nom' => ['required', 'string', 'max:255'],
            'client_nom' => ['required', 'string', 'max:255'],
            'tireur_signataire' => ['nullable', 'string', 'max:255'],
            'date_remise' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string'],
        ];

        if ($withPayment) {
            $rules += [
                'statut' => ['required', Rule::in(ChequeImpaye::STATUSES)],
                'date_paiement' => [Rule::requiredIf($request->input('statut') === 'paye'), 'nullable', 'date'],
                'mode_paiement' => [Rule::requiredIf($request->input('statut') === 'paye'), 'nullable', Rule::in(['espece', 'virement', 'cheque'])],
            ];
        }

        return $request->validate($rules);
    }

    private function serialize(ChequeImpaye $cheque): array
    {
        return [
            'id' => $cheque->id,
            'type' => $cheque->type,
            'numero_cheque' => $cheque->numero_cheque,
            'fournisseur_nom' => $cheque->fournisseur_nom,
            'client_nom' => $cheque->client_nom,
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
