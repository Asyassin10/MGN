<?php

namespace App\Http\Controllers;

use App\Models\Devis;
use App\Services\DevisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DevisController extends Controller
{
    public function index(Request $request, DevisService $service): Response
    {
        $filters = $request->only(['search', 'status', 'fournisseur_id']);

        return Inertia::render('Devis/Index', [
            'devis' => $service->list($filters),
            'filters' => $filters,
            'fournisseurs' => $service->options()['fournisseurs'],
            'depots' => $service->depotOptions(),
        ]);
    }

    public function create(DevisService $service): Response
    {
        return Inertia::render('Devis/Create', $service->options());
    }

    public function store(Request $request, DevisService $service): RedirectResponse
    {
        $data = $request->validate([
            'fournisseur_id' => ['required', 'integer', 'exists:fournisseurs,id'],
            'note' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.article_id' => ['required', 'integer', 'exists:articles,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $devis = $service->create($data, $request->user());

        return redirect()->route('devis.index')->with('success', 'Devis '.$devis->reference.' créé.');
    }

    public function validateDevis(Request $request, Devis $devis, DevisService $service): RedirectResponse
    {
        $data = $request->validate(['depot_id' => ['required', 'integer', 'exists:depots,id']]);

        try {
            $service->validate($devis, (int) $data['depot_id']);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first());
        }

        return back()->with('success', 'Devis validé : le stock du dépôt a été mis à jour.');
    }

    public function cancel(Devis $devis, DevisService $service): RedirectResponse
    {
        try {
            $service->cancel($devis);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first());
        }

        return back()->with('success', 'Devis annulé.');
    }

    public function pdf(Devis $devis, DevisService $service): HttpResponse
    {
        return $service->pdf($devis);
    }

    public function destroy(Devis $devis): RedirectResponse
    {
        if ($devis->status === Devis::STATUS_VALIDATED) {
            return back()->with('error', 'Un devis validé ne peut pas être supprimé : le stock a déjà été ajouté.');
        }

        $devis->delete();

        return back()->with('success', 'Devis supprimé.');
    }
}
