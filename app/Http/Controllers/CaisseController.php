<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaisseEntryRequest;
use App\Models\CaisseEntry;
use App\Models\Client;
use App\Models\Fournisseur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaisseController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = CaisseEntry::query()
            ->with(['client:id,nom', 'fournisseur:id,nom', 'user:id,name'])
            ->latest()
            ->paginate(100)
            ->through(fn (CaisseEntry $entry) => [
                'id' => $entry->id,
                'type' => $entry->type,
                'party' => $entry->client_id ? "client:{$entry->client_id}" : "fournisseur:{$entry->fournisseur_id}",
                'party_label' => $entry->client?->nom ?? $entry->fournisseur?->nom,
                'montant' => (float) $entry->montant,
                'note' => $entry->note,
                'created_by' => $entry->user?->name,
                'validated' => $entry->validated_at !== null,
                'created_at' => $entry->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Caisse/Index', [
            'entries' => $entries,
            'parties' => $this->partyOptions(),
            'kpis' => $request->user()?->isAdmin() ? $this->kpis() : null,
            'newParty' => session('newParty'),
        ]);
    }

    private function kpis(): array
    {
        $totalEntree = (float) CaisseEntry::query()->where('type', 'entree')->sum('montant');
        $totalSortie = (float) CaisseEntry::query()->where('type', 'sortie')->sum('montant');

        return [
            'total_entree' => round($totalEntree, 2),
            'total_sortie' => round($totalSortie, 2),
            'solde' => round($totalEntree - $totalSortie, 2),
        ];
    }

    public function store(StoreCaisseEntryRequest $request): RedirectResponse
    {
        $entry = new CaisseEntry($request->validated());
        $entry->user_id = $request->user()->id;

        if ($request->user()->isAdmin()) {
            $entry->validated_at = now();
            $entry->validated_by = $request->user()->id;
        }

        $entry->save();

        return back()->with('success', 'Mouvement de caisse enregistré.');
    }

    public function update(StoreCaisseEntryRequest $request, CaisseEntry $caisse): RedirectResponse
    {
        $caisse->update($request->validated());

        return back()->with('success', 'Mouvement de caisse mis à jour.');
    }

    public function destroy(CaisseEntry $caisse): RedirectResponse
    {
        $caisse->delete();

        return back()->with('success', 'Mouvement de caisse supprimé.');
    }

    public function approve(Request $request, CaisseEntry $caisse): RedirectResponse
    {
        $caisse->validated_at = now();
        $caisse->validated_by = $request->user()->id;
        $caisse->save();

        return back()->with('success', 'Mouvement validé.');
    }

    public function quickStoreClient(Request $request): RedirectResponse
    {
        $data = $request->validate(['nom' => ['required', 'string', 'max:255']]);

        $client = Client::create([
            ...$data,
            'source' => 'caisse',
            'note' => 'Créé depuis Caisse',
        ]);

        return back()->with([
            'success' => 'Client créé.',
            'newParty' => ['value' => "client:{$client->id}", 'label' => "Client — {$client->nom}"],
        ]);
    }

    public function quickStoreFournisseur(Request $request): RedirectResponse
    {
        $data = $request->validate(['nom' => ['required', 'string', 'max:255']]);

        $fournisseur = Fournisseur::create([
            ...$data,
            'source' => 'caisse',
            'note' => 'Créé depuis Caisse',
        ]);

        return back()->with([
            'success' => 'Fournisseur créé.',
            'newParty' => ['value' => "fournisseur:{$fournisseur->id}", 'label' => "Fournisseur — {$fournisseur->nom}"],
        ]);
    }

    private function partyOptions(): array
    {
        $clients = Client::query()->orderBy('nom')->get(['id', 'nom'])
            ->map(fn (Client $client) => ['value' => "client:{$client->id}", 'label' => "Client — {$client->nom}"]);

        $fournisseurs = Fournisseur::query()->orderBy('nom')->get(['id', 'nom'])
            ->map(fn (Fournisseur $fournisseur) => ['value' => "fournisseur:{$fournisseur->id}", 'label' => "Fournisseur — {$fournisseur->nom}"]);

        return $clients->concat($fournisseurs)->values()->all();
    }
}
