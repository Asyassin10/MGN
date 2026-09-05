<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFournisseurChequeRequest;
use App\Http\Requests\StoreFournisseurFactureRequest;
use App\Http\Requests\StoreFournisseurReleveCompteRequest;
use App\Http\Requests\StoreFournisseurRequest;
use App\Http\Requests\UpdateFournisseurRequest;
use App\Models\Fournisseur;
use App\Models\FournisseurCheque;
use App\Models\FournisseurFacture;
use App\Models\FournisseurReleveCompte;
use App\Services\FournisseurService;
use App\Support\DeleteBlockers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FournisseurController extends Controller
{
    public function index(Request $request, FournisseurService $service): Response|StreamedResponse
    {
        $filters = [...$request->only(['search', 'ville', 'balance_min', 'balance_max']), 'selected_ids' => $this->selectedIds($request)];

        if ($request->boolean('export')) {
            return $service->export($filters);
        }

        return Inertia::render('Fournisseurs/Index', [
            'fournisseurs' => $service->list($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Fournisseurs/Create');
    }

    public function relevesIndex(Request $request, FournisseurService $service): Response|StreamedResponse
    {
        $filters = [...$request->only(['search', 'fournisseur_id', 'date_from', 'date_to']), 'selected_ids' => $this->selectedIds($request)];

        if ($request->boolean('export')) {
            return $service->exportAllReleves($filters);
        }

        return Inertia::render('Fournisseurs/RelevesIndex', [
            'releves' => $service->relevesList($filters),
            'fournisseurs' => Fournisseur::query()->orderBy('nom')->get(['id', 'nom'])->map(fn (Fournisseur $fournisseur) => [
                'value' => (string) $fournisseur->id,
                'label' => $fournisseur->nom,
            ]),
            'filters' => $filters,
        ]);
    }

    public function store(StoreFournisseurRequest $request): RedirectResponse
    {
        Fournisseur::create($request->validated());

        return redirect()->route('fournisseurs.index')->with('success', 'Fournisseur créé. Ouvrez-le pour gérer ses relevés compte.');
    }

    public function show(Request $request, Fournisseur $fournisseur, FournisseurService $service): Response|StreamedResponse
    {
        if ($request->boolean('export')) {
            return $service->exportReleves($fournisseur, ['selected_ids' => $this->selectedIds($request)]);
        }

        return Inertia::render('Fournisseurs/Show', [
            ...$service->show($fournisseur, $request->all()),
            'filters' => $request->all(),
        ]);
    }

    public function update(UpdateFournisseurRequest $request, Fournisseur $fournisseur): RedirectResponse
    {
        $fournisseur->update($request->validated());

        return back()->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Fournisseur $fournisseur): RedirectResponse
    {
        $message = DeleteBlockers::message('ce fournisseur', [
            'relevés compte' => $fournisseur->releveComptes()->count(),
            'factures' => $fournisseur->factures()->count(),
            'chèques fournisseur' => $fournisseur->cheques()->count(),
        ]);

        if ($message) {
            return back()->with('error', $message);
        }

        $fournisseur->delete();

        return redirect()->route('fournisseurs.index')->with('success', 'Fournisseur supprimé.');
    }

    public function storeFacture(StoreFournisseurFactureRequest $request, Fournisseur $fournisseur): RedirectResponse
    {
        return back()->with('error', 'Sélectionnez un relevé compte avant d’ajouter une facture.');
    }

    public function storeReleve(StoreFournisseurReleveCompteRequest $request, Fournisseur $fournisseur): RedirectResponse
    {
        $fournisseur->releveComptes()->create($request->validated());

        return redirect()->route('fournisseurs.show', $fournisseur)->with('success', 'Relevé compte créé. Ouvrez-le pour gérer ses factures et paiements.');
    }

    public function showReleve(Request $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurService $service): Response|StreamedResponse
    {
        $export = $request->string('export')->toString();
        if ($export === 'factures') {
            return $service->exportReleveFactures($releve, [...$request->all(), 'selected_ids' => $this->selectedIds($request)]);
        }
        if ($export === 'payments') {
            return $service->exportRelevePayments($releve, [...$request->all(), 'selected_ids' => $this->selectedIds($request)]);
        }

        return Inertia::render('Fournisseurs/ReleveShow', [
            ...$service->releve($fournisseur, $releve, $request->all()),
            'filters' => $request->all(),
        ]);
    }

    public function updateReleve(StoreFournisseurReleveCompteRequest $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id, 404);
        $releve->update($request->validated());

        return back()->with('success', 'Relevé compte mis à jour.');
    }

    public function destroyReleve(Request $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id, 404);

        $message = DeleteBlockers::message('ce relevé', [
            'factures' => $releve->factures()->count(),
            'chèques' => $releve->cheques()->count(),
        ]);

        if ($message) {
            return back()->with('error', $message);
        }

        $releve->delete();

        if ($request->string('return')->toString() === 'index') {
            return redirect()->route('fournisseurs.releves.index')->with('success', 'Relevé compte supprimé.');
        }

        return redirect()->route('fournisseurs.show', $fournisseur)->with('success', 'Relevé compte supprimé.');
    }

    public function pdfReleve(Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurService $service): \Symfony\Component\HttpFoundation\Response
    {
        return $service->pdfReleve($fournisseur, $releve);
    }

    public function pdfPayment(Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurCheque $payment, FournisseurService $service): \Symfony\Component\HttpFoundation\Response
    {
        return $service->pdfCheque($fournisseur, $releve, $payment);
    }

    public function storeReleveFacture(StoreFournisseurFactureRequest $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id, 404);

        $releve->factures()->create([
            ...$request->validated(),
            'fournisseur_id' => $fournisseur->id,
        ]);

        return back()->with('success', 'Facture ajoutée.');
    }

    public function updateFacture(StoreFournisseurFactureRequest $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurFacture $facture): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id || $facture->fournisseur_releve_compte_id !== $releve->id, 404);
        $facture->update($request->validated());

        return back()->with('success', 'Facture mise à jour.');
    }

    public function destroyFacture(Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurFacture $facture): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id || $facture->fournisseur_releve_compte_id !== $releve->id, 404);
        $facture->delete();

        return back()->with('success', 'Facture supprimée.');
    }

    public function storeRelevePayment(StoreFournisseurChequeRequest $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id, 404);

        $releve->cheques()->create([
            ...$request->validated(),
            'fournisseur_id' => $fournisseur->id,
        ]);

        return back()->with('success', 'Paiement ajouté.');
    }

    public function updatePayment(StoreFournisseurChequeRequest $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurCheque $payment): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id || $payment->fournisseur_releve_compte_id !== $releve->id, 404);
        $payment->update($request->validated());

        return back()->with('success', 'Paiement mis à jour.');
    }

    public function destroyPayment(Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurCheque $payment): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id || $payment->fournisseur_releve_compte_id !== $releve->id, 404);
        $payment->delete();

        return back()->with('success', 'Paiement supprimé.');
    }

    public function updateChequeStatus(Request $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurCheque $cheque): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id || $cheque->fournisseur_releve_compte_id !== $releve->id, 404);
        $data = $request->validate(['statut' => ['sometimes', 'required', Rule::in(FournisseurCheque::STATUSES)], 'facture_recue' => ['sometimes', 'nullable', 'boolean'], 'facture_donnee' => ['sometimes', 'nullable', 'boolean']]);
        $cheque->update($data);

        return back()->with('success', 'Statut mis à jour.');
    }

    private function selectedIds(Request $request): array
    {
        return $request->validate([
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer', 'distinct'],
        ])['selected_ids'] ?? [];
    }
}
