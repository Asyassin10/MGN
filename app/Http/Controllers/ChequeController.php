<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChequeRequest;
use App\Models\Cheque;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Services\ChequeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeController extends Controller
{
    public function index(Request $request, ChequeService $service): Response|StreamedResponse
    {
        $filters = $request->only(['type', 'statut', 'banque', 'date_from', 'date_to', 'montant_min', 'montant_max', 'search']);

        if ($request->boolean('export')) {
            return $service->export($filters);
        }

        return Inertia::render('Cheques/Index', [
            'cheques' => $service->list($filters),
            'filters' => $filters,
            'tiers' => $service->tiers(),
            'banques' => $service->banques(),
        ]);
    }

    public function create(ChequeService $service): Response
    {
        return Inertia::render('Cheques/Create', [
            'tiers' => $service->tiers(),
            'banques' => $service->banques(),
        ]);
    }

    public function show(Cheque $cheque, ChequeService $service): Response
    {
        $cheque->load('tier');

        return Inertia::render('Cheques/Show', [
            'cheque' => $service->serialize($cheque),
        ]);
    }

    public function pdf(Cheque $cheque, ChequeService $service): \Symfony\Component\HttpFoundation\Response
    {
        return $service->pdf($cheque);
    }

    public function edit(Cheque $cheque, ChequeService $service): Response
    {
        $cheque->load('tier');

        return Inertia::render('Cheques/Edit', [
            'cheque' => [
                ...$service->serialize($cheque),
                'tier_value' => $cheque->tier_type && $cheque->tier_id
                    ? ($cheque->type.':'.$cheque->tier_id)
                    : '',
            ],
            'tiers' => $service->tiers(),
            'banques' => $service->banques(),
        ]);
    }

    public function store(StoreChequeRequest $request): RedirectResponse
    {
        $data = $this->prepareData($request);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('cheques', 'public');
        }

        Cheque::create($data);

        return back()->with('success', 'Chèque créé.');
    }

    public function update(StoreChequeRequest $request, Cheque $cheque): RedirectResponse
    {
        $data = $this->prepareData($request);

        if ($request->hasFile('attachment')) {
            if ($cheque->attachment) {
                Storage::disk('public')->delete($cheque->attachment);
            }
            $data['attachment'] = $request->file('attachment')->store('cheques', 'public');
        }

        $cheque->update($data);

        return back()->with('success', 'Chèque mis à jour.');
    }

    public function updateStatus(Request $request, Cheque $cheque): RedirectResponse
    {
        $data = $request->validate(['statut' => ['required', Rule::in(Cheque::STATUSES)]]);
        $cheque->update($data);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function destroy(Cheque $cheque): RedirectResponse
    {
        $cheque->delete();

        return back()->with('success', 'Chèque supprimé.');
    }

    private function prepareData(StoreChequeRequest $request): array
    {
        $data = $request->validated();
        unset($data['tier_value']);

        $tierValue = $request->string('tier_value')->toString();
        if ($tierValue !== '') {
            [$type, $id] = explode(':', $tierValue) + [null, null];
            $data['tier_id'] = $id;
            $data['tier_type'] = $type === 'client' ? Client::class : Fournisseur::class;
        }

        return $data;
    }
}
