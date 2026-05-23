<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFournisseurChequeRequest;
use App\Http\Requests\StoreFournisseurFactureRequest;
use App\Http\Requests\StoreFournisseurPaymentRequest;
use App\Http\Requests\StoreFournisseurReleveCompteRequest;
use App\Http\Requests\StoreFournisseurRequest;
use App\Http\Requests\UpdateFournisseurRequest;
use App\Models\Fournisseur;
use App\Models\FournisseurCheque;
use App\Models\FournisseurFacture;
use App\Models\FournisseurPayment;
use App\Models\FournisseurReleveCompte;
use App\Models\Cheque;
use App\Services\FournisseurService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FournisseurController extends Controller
{
    public function index(Request $request, FournisseurService $service): Response|StreamedResponse
    {
        $filters = $request->only(['search', 'ville', 'balance_min', 'balance_max']);

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
        $filters = $request->only(['search', 'fournisseur_id', 'date_from', 'date_to']);

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
            return $service->exportReleves($fournisseur);
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
        if (
            $fournisseur->releveComptes()->exists()
            || $fournisseur->factures()->exists()
            || $fournisseur->payments()->exists()
            || $fournisseur->cheques()->exists()
            || Cheque::query()->whereMorphedTo('tier', $fournisseur)->exists()
        ) {
            return back()->with('error', 'Impossible de supprimer ce fournisseur : son historique doit être supprimé d’abord.');
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
            return $service->exportReleveFactures($releve, $request->all());
        }
        if ($export === 'payments') {
            return $service->exportRelevePayments($releve, $request->all());
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

        if ($releve->factures()->exists() || $releve->payments()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce relevé : supprimez d’abord ses factures et paiements.');
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

    public function pdfPayment(Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurPayment $payment, FournisseurService $service): \Symfony\Component\HttpFoundation\Response
    {
        return $service->pdfPayment($fournisseur, $releve, $payment);
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

    public function storePayment(StoreFournisseurPaymentRequest $request, Fournisseur $fournisseur): RedirectResponse
    {
        return back()->with('error', 'Sélectionnez un relevé compte avant d’ajouter un paiement.');
    }

    public function storeRelevePayment(StoreFournisseurPaymentRequest $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id, 404);

        $releve->payments()->create([
            ...$request->validated(),
            'fournisseur_id' => $fournisseur->id,
            'fournisseur_cheque_id' => null,
            'mode' => 'cheque',
            'reference' => $request->validated('numero_cheque'),
        ]);

        return back()->with('success', 'Paiement ajouté.');
    }

    public function updatePayment(StoreFournisseurPaymentRequest $request, Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurPayment $payment): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id || $payment->fournisseur_releve_compte_id !== $releve->id, 404);
        $payment->update([
            ...$request->validated(),
            'fournisseur_cheque_id' => null,
            'mode' => 'cheque',
            'reference' => $request->validated('numero_cheque'),
        ]);

        return back()->with('success', 'Paiement mis à jour.');
    }

    public function destroyPayment(Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurPayment $payment): RedirectResponse
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id || $payment->fournisseur_releve_compte_id !== $releve->id, 404);
        $payment->delete();

        return back()->with('success', 'Paiement supprimé.');
    }

    public function storeCheque(StoreFournisseurChequeRequest $request, Fournisseur $fournisseur, FournisseurService $service): RedirectResponse
    {
        DB::transaction(function () use ($request, $fournisseur, $service): void {
            $cheque = $fournisseur->cheques()->create($request->validated());
            $service->syncChequePayment($cheque);
        });

        return back()->with('success', 'Chèque ajouté.');
    }

    public function updateCheque(StoreFournisseurChequeRequest $request, Fournisseur $fournisseur, FournisseurCheque $cheque, FournisseurService $service): RedirectResponse
    {
        abort_if($cheque->fournisseur_id !== $fournisseur->id, 404);

        DB::transaction(function () use ($request, $cheque, $service): void {
            $cheque->update($request->validated());
            $service->setChequeStatus($cheque, $request->string('statut')->toString());
        });

        return back()->with('success', 'Chèque mis à jour.');
    }

    public function updateChequeStatus(Request $request, FournisseurCheque $cheque, FournisseurService $service): RedirectResponse
    {
        $data = $request->validate(['statut' => ['required', Rule::in(FournisseurCheque::STATUSES)]]);
        $service->setChequeStatus($cheque, $data['statut']);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function destroyCheque(Fournisseur $fournisseur, FournisseurCheque $cheque): RedirectResponse
    {
        abort_if($cheque->fournisseur_id !== $fournisseur->id, 404);
        $cheque->payment()->delete();
        $cheque->delete();

        return back()->with('success', 'Chèque supprimé.');
    }
}
