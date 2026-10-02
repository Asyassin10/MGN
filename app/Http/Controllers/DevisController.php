<?php

namespace App\Http\Controllers;

use App\Models\Devis;
use App\Models\DevisLine;
use App\Services\DevisService;
use Closure;
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
        $options = $service->options();

        return Inertia::render('Devis/Index', [
            'devis' => $service->list($filters),
            'filters' => $filters,
            'fournisseurs' => $options['fournisseurs'],
            'groups' => $options['groups'],
            'articles' => $options['articles'],
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

        return redirect()->route('devis.index')->with('success', 'Bon de commande '.$devis->reference.' créé.');
    }

    public function addLine(Request $request, Devis $devis, DevisService $service): RedirectResponse
    {
        $data = $request->validate([
            'article_id' => ['required', 'integer', 'exists:articles,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        return $this->run(fn () => $service->addLine($devis, (int) $data['article_id'], (int) $data['quantity']), 'Article ajouté au bon de commande.');
    }

    public function updateLine(Request $request, Devis $devis, DevisLine $line, DevisService $service): RedirectResponse
    {
        abort_if($line->devis_id !== $devis->id, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        return $this->run(fn () => $service->updateLine($devis, $line, (int) $data['quantity']), 'Quantité mise à jour.');
    }

    public function removeLine(Devis $devis, DevisLine $line, DevisService $service): RedirectResponse
    {
        abort_if($line->devis_id !== $devis->id, 404);

        return $this->run(fn () => $service->removeLine($devis, $line), 'Article retiré du bon de commande.');
    }

    public function validateLine(Request $request, Devis $devis, DevisLine $line, DevisService $service): RedirectResponse
    {
        abort_if($line->devis_id !== $devis->id, 404);
        $data = $request->validate([
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->run(
            fn () => $service->validateLine($devis, $line, (int) $data['depot_id'], isset($data['quantity']) ? (int) $data['quantity'] : null),
            'Article validé : stock ajouté au dépôt.',
        );
    }

    public function cancel(Devis $devis, DevisService $service): RedirectResponse
    {
        return $this->run(fn () => $service->cancel($devis), 'Bon de commande annulé.');
    }

    public function pdf(Devis $devis, DevisService $service): HttpResponse
    {
        return $service->pdf($devis);
    }

    public function destroy(Devis $devis): RedirectResponse
    {
        if (in_array($devis->status, [Devis::STATUS_VALIDATED, Devis::STATUS_PARTIAL], true)) {
            return back()->with('error', 'Un bon de commande validé (même partiellement) ne peut pas être supprimé : du stock a déjà été ajouté.');
        }

        $devis->delete();

        return back()->with('success', 'Bon de commande supprimé.');
    }

    private function run(Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first());
        }

        return back()->with('success', $success);
    }
}
