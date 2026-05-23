<?php

namespace App\Services;

use App\Models\Cheque;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Support\ExcelExport;
use App\Support\FinancePdf;
use App\Support\DownloadFilename;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest('date_echeance')
            ->paginate(100)
            ->withQueryString()
            ->through(fn (Cheque $cheque) => $this->serialize($cheque));
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)->latest('date_echeance')->get();
        return ExcelExport::download('cheques-export', ['Numero', 'Type', 'Tier', 'Banque', 'Montant', 'Emission', 'Echeance', 'Statut'], $rows->map(fn (Cheque $cheque) => [
            $cheque->numero_cheque,
            $cheque->type,
            $cheque->tier?->nom,
            $cheque->banque,
            $cheque->montant,
            $cheque->date_emission?->format('Y-m-d'),
            $cheque->date_echeance?->format('Y-m-d'),
            $cheque->statut,
        ]));
    }

    public function pdf(Cheque $cheque): Response
    {
        $cheque->load('tier');

        return FinancePdf::preview([
            'title' => 'Cheque '.$cheque->numero_cheque,
            'subtitle' => 'Cheque',
            'brand' => 'Droguerie Palmeraie',
            'meta' => [
                'Type' => $cheque->type,
                'Tier' => $cheque->tier?->nom,
                'Statut' => $cheque->statut,
            ],
            'columns' => [
                ['key' => 'numero', 'label' => 'Numero'],
                ['key' => 'banque', 'label' => 'Banque'],
                ['key' => 'tireur', 'label' => 'Tireur / signataire'],
                ['key' => 'emission', 'label' => 'Emission'],
                ['key' => 'echeance', 'label' => 'Echeance'],
                ['key' => 'montant', 'label' => 'Montant', 'align' => 'right'],
            ],
            'rows' => [[
                'numero' => $cheque->numero_cheque,
                'banque' => $cheque->banque,
                'tireur' => $cheque->tireur_signataire ?: '-',
                'emission' => $cheque->date_emission?->format('d/m/Y'),
                'echeance' => $cheque->date_echeance?->format('d/m/Y'),
                'montant' => number_format((float) $cheque->montant, 2, ',', ' ').' MAD',
            ]],
            'note' => $cheque->note,
        ], DownloadFilename::pdf('cheque', $cheque->type, $cheque->numero_cheque ?: (string) $cheque->id));
    }

    public function tiers(): array
    {
        return [
            'clients' => Client::query()->orderBy('nom')->get(['id', 'nom'])->map(fn ($client) => [
                'value' => 'client:'.$client->id,
                'label' => $client->nom,
            ]),
            'fournisseurs' => Fournisseur::query()->orderBy('nom')->get(['id', 'nom'])->map(fn ($fournisseur) => [
                'value' => 'fournisseur:'.$fournisseur->id,
                'label' => $fournisseur->nom,
            ]),
        ];
    }

    public function banques(): array
    {
        return Cheque::query()
            ->select('banque')
            ->whereNotNull('banque')
            ->distinct()
            ->orderBy('banque')
            ->pluck('banque')
            ->map(fn (string $banque) => ['value' => $banque, 'label' => $banque])
            ->all();
    }

    private function baseQuery(array $filters): Builder
    {
        return Cheque::query()
            ->with('tier')
            ->when($filters['type'] ?? null, fn ($query, $value) => $query->where('type', $value))
            ->when($filters['statut'] ?? null, fn ($query, $value) => $query->where('statut', $value))
            ->when($filters['banque'] ?? null, fn ($query, $value) => $query->where('banque', 'like', "%{$value}%"))
            ->when($filters['date_from'] ?? null, fn ($query, $value) => $query->whereDate('date_echeance', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($query, $value) => $query->whereDate('date_echeance', '<=', $value))
            ->when($filters['montant_min'] ?? null, fn ($query, $value) => $query->where('montant', '>=', $value))
            ->when($filters['montant_max'] ?? null, fn ($query, $value) => $query->where('montant', '<=', $value))
            ->when($filters['search'] ?? null, function ($query, $value): void {
                $query->where(fn ($inner) => $inner
                    ->where('numero_cheque', 'like', "%{$value}%")
                    ->orWhere('tireur_signataire', 'like', "%{$value}%"));
            });
    }

    public function serialize(Cheque $cheque): array
    {
        return [
            'id' => $cheque->id,
            'type' => $cheque->type,
            'tier' => $cheque->tier?->nom,
            'numero_cheque' => $cheque->numero_cheque,
            'banque' => $cheque->banque,
            'tireur_signataire' => $cheque->tireur_signataire,
            'montant' => (float) $cheque->montant,
            'date_emission' => $cheque->date_emission?->format('Y-m-d'),
            'date_echeance' => $cheque->date_echeance?->format('Y-m-d'),
            'statut' => $cheque->statut,
            'note' => $cheque->note,
        ];
    }
}
