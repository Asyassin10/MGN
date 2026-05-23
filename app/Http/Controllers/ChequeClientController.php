<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChequeClientRequest;
use App\Models\Bank;
use App\Models\ChequeClient;
use App\Services\ChequeClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeClientController extends Controller
{
    public function index(Request $request, ChequeClientService $service): Response|StreamedResponse
    {
        $filters = $request->only(['search', 'client_id', 'statut', 'banque', 'date_emission_from', 'date_emission_to', 'date_echeance_from', 'date_echeance_to']);

        if ($request->boolean('export')) {
            return $service->export($filters);
        }

        return Inertia::render('ChequeClients/Index', [
            'cheques' => $service->list($filters),
            'filters' => $filters,
            ...$service->options(),
        ]);
    }

    public function create(ChequeClientService $service): Response
    {
        return Inertia::render('ChequeClients/Create', $service->options());
    }

    public function store(StoreChequeClientRequest $request): RedirectResponse
    {
        $data = $this->prepareData($request);

        if ($request->hasFile('piece_jointe')) {
            $data['piece_jointe'] = $request->file('piece_jointe')->store('cheques/clients', 'public');
        }

        ChequeClient::create($data);

        return redirect()->route('cheque-clients.index')->with('success', 'Chèque client créé.');
    }

    public function show(ChequeClient $chequeClient, ChequeClientService $service): Response
    {
        $chequeClient->load('client');

        return Inertia::render('ChequeClients/Show', [
            'cheque' => $service->serialize($chequeClient),
        ]);
    }

    public function pdf(ChequeClient $chequeClient, ChequeClientService $service): \Symfony\Component\HttpFoundation\Response
    {
        return $service->pdf($chequeClient);
    }

    public function edit(ChequeClient $chequeClient, ChequeClientService $service): Response
    {
        $chequeClient->load('client');

        return Inertia::render('ChequeClients/Edit', [
            'cheque' => $service->serialize($chequeClient),
            ...$service->options(),
        ]);
    }

    public function update(StoreChequeClientRequest $request, ChequeClient $chequeClient): RedirectResponse
    {
        $data = $this->prepareData($request);

        if ($request->hasFile('piece_jointe')) {
            if ($chequeClient->piece_jointe) {
                Storage::disk('public')->delete($chequeClient->piece_jointe);
            }

            $data['piece_jointe'] = $request->file('piece_jointe')->store('cheques/clients', 'public');
        }

        $chequeClient->update($data);

        return redirect()->route('cheque-clients.index')->with('success', 'Chèque client mis à jour.');
    }

    public function updateStatus(Request $request, ChequeClient $chequeClient): RedirectResponse
    {
        $data = $request->validate(['statut' => ['required', Rule::in(ChequeClient::STATUSES)]]);
        $chequeClient->update($data);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function destroy(ChequeClient $chequeClient): RedirectResponse
    {
        if ($chequeClient->piece_jointe) {
            Storage::disk('public')->delete($chequeClient->piece_jointe);
        }

        $chequeClient->delete();

        return back()->with('success', 'Chèque client supprimé.');
    }

    private function prepareData(StoreChequeClientRequest $request): array
    {
        $data = $request->validated();
        $bankName = trim((string) ($data['banque'] ?? ''));

        if (($data['bank_id'] ?? null) && $bankName === '') {
            $bankName = Bank::find($data['bank_id'])?->name ?? '';
        }

        $data['banque'] = $bankName ?: null;

        return $data;
    }
}
