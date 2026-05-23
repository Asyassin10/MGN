<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\ChequeFournisseur;
use App\Models\ChequePartyFournisseur;
use App\Support\ExcelExport;
use App\Support\FinancePdf;
use App\Support\DownloadFilename;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeFournisseurService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest('date_echeance')
            ->paginate(100)
            ->withQueryString()
            ->through(fn (ChequeFournisseur $cheque) => $this->serialize($cheque));
    }

    public function options(): array
    {
        return [
            'fournisseurs' => ChequePartyFournisseur::query()->orderBy('nom')->get(['id', 'nom'])->map(fn (ChequePartyFournisseur $fournisseur) => [
                'value' => $fournisseur->id,
                'label' => $fournisseur->nom,
            ])->all(),
            'banks' => Bank::query()->orderBy('name')->get(['id', 'name'])->map(fn (Bank $bank) => [
                'value' => $bank->id,
                'label' => $bank->name,
            ])->all(),
            'banques' => Bank::query()
                ->orderBy('name')
                ->pluck('name')
                ->map(fn (string $name) => ['value' => $name, 'label' => $name])
                ->all(),
        ];
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)->latest('date_echeance')->get();
        return ExcelExport::download('cheque-fournisseurs-export', ['Numero', 'Fournisseur', 'Type', 'Banque', 'Montant', 'Emission', 'Echeance', 'Statut'], $rows->map(fn (ChequeFournisseur $cheque) => [
            $cheque->numero_cheque,
            $cheque->fournisseur?->nom,
            $cheque->type,
            $cheque->banque,
            $cheque->montant,
            $cheque->date_emission?->format('Y-m-d'),
            $cheque->date_echeance?->format('Y-m-d'),
            $cheque->statut,
        ]));
    }

    public function pdf(ChequeFournisseur $cheque): Response
    {
        $cheque->load(['fournisseur', 'bank']);

        return FinancePdf::download([
            'title' => 'Cheque fournisseur '.$cheque->numero_cheque,
            'subtitle' => 'Cheque fournisseur',
            'brand' => 'Droguerie Palmeraie',
            'meta' => [
                'Fournisseur' => $cheque->fournisseur?->nom,
                'Type' => $cheque->type,
                'Statut' => $cheque->statut,
            ],
            'columns' => [
                ['key' => 'numero', 'label' => 'Numero'],
                ['key' => 'banque', 'label' => 'Banque'],
                ['key' => 'emission', 'label' => 'Emission'],
                ['key' => 'echeance', 'label' => 'Echeance'],
                ['key' => 'montant', 'label' => 'Montant', 'align' => 'right'],
            ],
            'rows' => [[
                'numero' => $cheque->numero_cheque,
                'banque' => $cheque->banque ?: $cheque->bank?->name,
                'emission' => $cheque->date_emission?->format('d/m/Y'),
                'echeance' => $cheque->date_echeance?->format('d/m/Y'),
                'montant' => number_format((float) $cheque->montant, 2, ',', ' ').' MAD',
            ]],
            'note' => $cheque->motif,
        ], DownloadFilename::pdf('cheque-fournisseur', $cheque->numero_cheque ?: (string) $cheque->id, $cheque->fournisseur?->nom ?: 'fournisseur'));
    }

    public function serialize(ChequeFournisseur $cheque): array
    {
        return [
            'id' => $cheque->id,
            'type' => $cheque->type,
            'numero_cheque' => $cheque->numero_cheque,
            'fournisseur_id' => $cheque->fournisseur_id,
            'fournisseur' => $cheque->fournisseur?->nom,
            'bank_id' => $cheque->bank_id,
            'bank' => $cheque->bank?->name,
            'banque' => $cheque->banque ?: $cheque->bank?->name,
            'montant' => (float) $cheque->montant,
            'tireur_signataire' => $cheque->tireur_signataire,
            'motif' => $cheque->motif,
            'date_emission' => $cheque->date_emission?->format('Y-m-d'),
            'date_echeance' => $cheque->date_echeance?->format('Y-m-d'),
            'statut' => $cheque->statut,
            'note' => $cheque->note,
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        return ChequeFournisseur::query()
            ->with(['fournisseur', 'bank'])
            ->when($filters['fournisseur_id'] ?? null, fn ($query, $value) => $query->where('fournisseur_id', $value))
            ->when($filters['statut'] ?? null, fn ($query, $value) => $query->where('statut', $value))
            ->when($filters['banque'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner->where('banque', 'like', "%{$value}%")->orWhereHas('bank', fn ($bank) => $bank->where('name', 'like', "%{$value}%"))))
            ->when($filters['date_emission_from'] ?? null, fn ($query, $value) => $query->whereDate('date_emission', '>=', $value))
            ->when($filters['date_emission_to'] ?? null, fn ($query, $value) => $query->whereDate('date_emission', '<=', $value))
            ->when($filters['date_echeance_from'] ?? null, fn ($query, $value) => $query->whereDate('date_echeance', '>=', $value))
            ->when($filters['date_echeance_to'] ?? null, fn ($query, $value) => $query->whereDate('date_echeance', '<=', $value))
            ->when($filters['search'] ?? null, function ($query, $value): void {
                $query->where(fn ($inner) => $inner
                    ->where('numero_cheque', 'like', "%{$value}%")
                    ->orWhere('banque', 'like', "%{$value}%")
                    ->orWhere('motif', 'like', "%{$value}%")
                    ->orWhere('tireur_signataire', 'like', "%{$value}%")
                    ->orWhereHas('fournisseur', fn ($fournisseur) => $fournisseur->where('nom', 'like', "%{$value}%")));
            });
    }
}
