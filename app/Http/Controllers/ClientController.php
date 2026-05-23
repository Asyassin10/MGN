<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientEntryRequest;
use App\Http\Requests\StoreClientPaymentRequest;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\ClientEntry;
use App\Models\ClientPayment;
use App\Models\Cheque;
use App\Services\ClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request, ClientService $service): Response|StreamedResponse
    {
        $filters = $request->only(['search', 'ville', 'balance_min', 'balance_max']);

        if ($request->boolean('export')) {
            return $service->export($filters);
        }

        return Inertia::render('Clients/Index', [
            'clients' => $service->list($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        return redirect()->route('clients.show', $client)->with('success', 'Client créé.');
    }

    public function show(Request $request, Client $client, ClientService $service): Response|StreamedResponse
    {
        $export = $request->string('export')->toString();
        if ($export === 'entries') {
            return $service->exportEntries($client, $request->all());
        }
        if ($export === 'payments') {
            return $service->exportPayments($client, $request->all());
        }

        return Inertia::render('Clients/Show', [
            ...$service->show($client, $request->all()),
            'filters' => $request->all(),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return back()->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->entries()->exists() || $client->payments()->exists() || Cheque::query()->whereMorphedTo('tier', $client)->exists()) {
            return back()->with('error', 'Impossible de supprimer ce client : son historique doit être supprimé d’abord.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client supprimé.');
    }

    public function storeEntry(StoreClientEntryRequest $request, Client $client): RedirectResponse
    {
        $client->entries()->create($request->validated());

        return back()->with('success', 'Entrée ajoutée.');
    }

    public function updateEntry(StoreClientEntryRequest $request, Client $client, ClientEntry $entry): RedirectResponse
    {
        abort_if($entry->client_id !== $client->id, 404);
        $entry->update($request->validated());

        return back()->with('success', 'Entrée mise à jour.');
    }

    public function destroyEntry(Client $client, ClientEntry $entry): RedirectResponse
    {
        abort_if($entry->client_id !== $client->id, 404);
        $entry->delete();

        return back()->with('success', 'Entrée supprimée.');
    }

    public function storePayment(StoreClientPaymentRequest $request, Client $client): RedirectResponse
    {
        $client->payments()->create($request->validated());

        return back()->with('success', 'Paiement ajouté.');
    }

    public function updatePayment(StoreClientPaymentRequest $request, Client $client, ClientPayment $payment): RedirectResponse
    {
        abort_if($payment->client_id !== $client->id, 404);
        $payment->update($request->validated());

        return back()->with('success', 'Paiement mis à jour.');
    }

    public function destroyPayment(Client $client, ClientPayment $payment): RedirectResponse
    {
        abort_if($payment->client_id !== $client->id, 404);
        $payment->delete();

        return back()->with('success', 'Paiement supprimé.');
    }

    public function pdfPayment(Client $client, ClientPayment $payment, ClientService $service): \Symfony\Component\HttpFoundation\Response
    {
        return $service->pdfPayment($client, $payment);
    }
}
