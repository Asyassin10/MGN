<?php

namespace App\Http\Controllers;

use App\Models\BonLivraison;
use App\Models\BonLivraisonLine;
use App\Services\BonLivraisonService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BonLivraisonController extends Controller
{
    public function index(Request $request, BonLivraisonService $service): Response
    {
        $filters = $request->only(['search', 'status', 'created_from', 'created_to']);
        $options = $service->options();

        return Inertia::render('Livraisons/Index', [
            'bons' => $service->list($filters),
            'filters' => $filters,
            'depots' => $options['depots'],
            'groups' => $options['groups'],
            'articles' => $options['articles'],
        ]);
    }

    public function create(BonLivraisonService $service): Response
    {
        return Inertia::render('Livraisons/Create', $service->options());
    }

    public function store(Request $request, BonLivraisonService $service): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'client_nom' => ['nullable', 'string', 'max:255'],
            'livreur_nom' => ['nullable', 'string', 'max:255'],
            'mode_paiement' => ['required', Rule::in(array_keys(BonLivraison::PAYMENT_MODES))],
            'note' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            ...$this->lineRules('lines.*.'),
        ]);

        $bon = $service->create($data, $request->user());

        return redirect()->route('livraisons.index')->with('success', 'Devis '.$bon->reference.' enregistré. Le stock sera retiré à la validation.');
    }

    public function update(Request $request, BonLivraison $livraison, BonLivraisonService $service): RedirectResponse
    {
        $data = $request->validate([
            'livreur_nom' => ['nullable', 'string', 'max:255'],
            'mode_paiement' => ['required', Rule::in(array_keys(BonLivraison::PAYMENT_MODES))],
        ]);

        return $this->run(fn () => $service->updateHeader($livraison, $data), 'Devis mis à jour.');
    }

    public function addLine(Request $request, BonLivraison $livraison, BonLivraisonService $service): RedirectResponse
    {
        $data = $request->validate($this->lineRules());

        return $this->run(fn () => $service->addLine($livraison, $data), 'Article ajouté au devis.');
    }

    public function updateLine(Request $request, BonLivraison $livraison, BonLivraisonLine $line, BonLivraisonService $service): RedirectResponse
    {
        abort_if($line->bon_livraison_id !== $livraison->id, 404);
        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
            'prix' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['nullable', Rule::in(array_keys(BonLivraisonLine::PRICE_TYPES))],
        ]);

        return $this->run(fn () => $service->updateLine($livraison, $line, $data), 'Ligne mise à jour.');
    }

    public function removeLine(BonLivraison $livraison, BonLivraisonLine $line, BonLivraisonService $service): RedirectResponse
    {
        abort_if($line->bon_livraison_id !== $livraison->id, 404);

        return $this->run(fn () => $service->removeLine($livraison, $line), 'Article retiré du devis.');
    }

    public function validateLine(Request $request, BonLivraison $livraison, BonLivraisonLine $line, BonLivraisonService $service): RedirectResponse
    {
        abort_if($line->bon_livraison_id !== $livraison->id, 404);
        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
            'prix' => ['nullable', 'numeric', 'min:0'],
        ]);

        return $this->run(
            fn () => $service->validateLine($livraison, $line, isset($data['quantity']) ? (int) $data['quantity'] : null, isset($data['prix']) ? (float) $data['prix'] : null),
            'Article validé : stock retiré du dépôt.',
        );
    }

    public function cancel(BonLivraison $livraison, BonLivraisonService $service): RedirectResponse
    {
        return $this->run(fn () => $service->cancel($livraison), 'Devis annulé.');
    }

    public function pdf(BonLivraison $livraison, BonLivraisonService $service): HttpResponse
    {
        return $service->pdf($livraison);
    }

    public function destroy(BonLivraison $livraison, BonLivraisonService $service): RedirectResponse
    {
        try {
            $service->delete($livraison);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first());
        }

        return back()->with('success', 'Devis supprimé (le stock déjà retiré a été rétabli).');
    }

    private function lineRules(string $prefix = ''): array
    {
        return [
            $prefix.'article_id' => ['required', 'integer', 'exists:articles,id'],
            $prefix.'depot_id' => ['required', 'integer', 'exists:depots,id'],
            $prefix.'quantity' => ['required', 'integer', 'min:1'],
            $prefix.'price_type' => ['required', Rule::in(array_keys(BonLivraisonLine::PRICE_TYPES))],
            $prefix.'prix' => ['required', 'numeric', 'min:0'],
        ];
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
