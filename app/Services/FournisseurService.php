<?php

namespace App\Services;

use App\Models\Fournisseur;
use App\Models\FournisseurCheque;
use App\Models\FournisseurPayment;
use App\Models\FournisseurReleveCompte;
use App\Support\ExcelExport;
use App\Support\FinancePdf;
use App\Support\DownloadFilename;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FournisseurService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest()
            ->paginate(100)
            ->withQueryString()
            ->through(fn (Fournisseur $fournisseur) => $this->serialize($fournisseur));
    }

    public function relevesList(array $filters): LengthAwarePaginator
    {
        return $this->relevesQuery($filters)
            ->latest('date_releve')
            ->paginate(100)
            ->withQueryString()
            ->through(fn (FournisseurReleveCompte $releve) => $this->serializeGlobalReleve($releve));
    }

    public function topBalances(): array
    {
        return Fournisseur::query()
            ->withSum('factures', 'montant')
            ->withSum('payments', 'montant')
            ->get()
            ->map(fn (Fournisseur $fournisseur) => $this->serialize($fournisseur))
            ->sortByDesc('balance')
            ->take(8)
            ->values()
            ->all();
    }

    public function show(Fournisseur $fournisseur, array $filters): array
    {
        $fournisseur->loadSum('factures', 'montant')->loadSum('payments', 'montant');

        return [
            'fournisseur' => $this->serialize($fournisseur),
            'releves' => $fournisseur->releveComptes()
                ->withSum('factures', 'montant')
                ->withSum('payments', 'montant')
                ->latest('date_releve')
                ->paginate(100)
                ->withQueryString()
                ->through(fn (FournisseurReleveCompte $releve) => $this->serializeReleve($releve)),
        ];
    }

    public function releve(Fournisseur $fournisseur, FournisseurReleveCompte $releve, array $filters): array
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id, 404);

        $fournisseur->loadSum('factures', 'montant')->loadSum('payments', 'montant');
        $releve->loadSum('factures', 'montant')->loadSum('payments', 'montant');
        $facturesQuery = $this->facturesQuery($releve, $filters);
        $paymentsQuery = $this->paymentsQuery($releve, $filters);

        return [
            'fournisseur' => $this->serialize($fournisseur),
            'releve' => $this->serializeReleve($releve),
            'factures' => $facturesQuery
                ->latest('date_facture')
                ->paginate(100, ['*'], 'factures_page')
                ->withQueryString()
                ->through(fn ($facture) => [
                    'id' => $facture->id,
                    'numero_facture' => $facture->numero_facture,
                    'date_facture' => $facture->date_facture?->format('Y-m-d'),
                    'montant' => (float) $facture->montant,
                    'note' => $facture->note,
                ]),
            'payments' => $paymentsQuery
                ->latest('date_paiement')
                ->paginate(100, ['*'], 'payments_page')
                ->withQueryString()
                ->through(fn ($payment) => [
                    'id' => $payment->id,
                    'date_paiement' => $payment->date_paiement?->format('Y-m-d'),
                    'montant' => (float) $payment->montant,
                    'numero_cheque' => $payment->numero_cheque ?: $payment->reference,
                    'banque' => $payment->banque,
                    'date_echeance' => $payment->date_echeance?->format('Y-m-d'),
                    'note' => $payment->note,
                ]),
        ];
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)
            ->latest()
            ->get()
            ->map(fn (Fournisseur $fournisseur) => [
                $fournisseur->nom,
                $fournisseur->ville,
                $fournisseur->telephone,
                round((float) ($fournisseur->factures_sum_montant ?? 0), 2),
                round((float) ($fournisseur->payments_sum_montant ?? 0), 2),
                round(((float) ($fournisseur->factures_sum_montant ?? 0)) - ((float) ($fournisseur->payments_sum_montant ?? 0)), 2),
            ]);

        return ExcelExport::download('fournisseurs-export', ['Nom', 'Ville', 'Telephone', 'Total factures', 'Total paye', 'Solde'], $rows);
    }

    public function exportReleves(Fournisseur $fournisseur): StreamedResponse
    {
        $rows = $fournisseur->releveComptes()
            ->withSum('factures', 'montant')
            ->withSum('payments', 'montant')
            ->latest('date_releve')
            ->get()
            ->map(fn (FournisseurReleveCompte $releve) => [
                $releve->code_client,
                $releve->date_releve?->format('Y-m-d'),
                round((float) ($releve->factures_sum_montant ?? 0), 2),
                round((float) ($releve->payments_sum_montant ?? 0), 2),
                round(((float) ($releve->factures_sum_montant ?? 0)) - ((float) ($releve->payments_sum_montant ?? 0)), 2),
            ]);

        return ExcelExport::download('fournisseur-'.$fournisseur->id.'-releves-export', ['Code client', 'Date releve', 'Total factures', 'Total paiements', 'Solde'], $rows);
    }

    public function exportAllReleves(array $filters): StreamedResponse
    {
        $rows = $this->relevesQuery($filters)
            ->latest('date_releve')
            ->get()
            ->map(fn (FournisseurReleveCompte $releve) => [
                $releve->fournisseur->nom,
                $releve->code_client,
                $releve->date_releve?->format('Y-m-d'),
                round((float) ($releve->factures_sum_montant ?? 0), 2),
                round((float) ($releve->payments_sum_montant ?? 0), 2),
                round(((float) ($releve->factures_sum_montant ?? 0)) - ((float) ($releve->payments_sum_montant ?? 0)), 2),
            ]);

        return ExcelExport::download('releves-compte-fournisseurs-export', ['Fournisseur', 'Code client', 'Date releve', 'Total factures', 'Total paiements', 'Solde'], $rows);
    }

    public function exportReleveFactures(FournisseurReleveCompte $releve, array $filters): StreamedResponse
    {
        $rows = $this->facturesQuery($releve, $filters)
            ->latest('date_facture')
            ->get()
            ->map(fn ($facture) => [
                $facture->date_facture?->format('Y-m-d'),
                $facture->numero_facture,
                $facture->montant,
                $facture->note,
            ]);

        return ExcelExport::download('releve-'.$releve->id.'-factures-export', ['Date facture', 'N facture', 'Montant', 'Note'], $rows);
    }

    public function exportRelevePayments(FournisseurReleveCompte $releve, array $filters): StreamedResponse
    {
        $rows = $this->paymentsQuery($releve, $filters)
            ->latest('date_paiement')
            ->get()
            ->map(fn (FournisseurPayment $payment) => [
                $payment->date_paiement?->format('Y-m-d'),
                $payment->numero_cheque ?: $payment->reference,
                $payment->banque,
                $payment->date_echeance?->format('Y-m-d'),
                $payment->montant,
                $payment->note,
            ]);

        return ExcelExport::download('releve-'.$releve->id.'-paiements-export', ['Date paiement', 'N cheque', 'Banque', 'Echeance', 'Montant', 'Note'], $rows);
    }

    public function pdfReleve(Fournisseur $fournisseur, FournisseurReleveCompte $releve): Response
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id, 404);

        $releve->load(['factures', 'payments']);
        $releve->loadSum('factures', 'montant')->loadSum('payments', 'montant');

        $rows = $releve->factures
            ->map(fn ($facture) => [
                'date' => $facture->date_facture?->format('d/m/Y'),
                'designation' => $facture->numero_facture,
                'montant' => number_format((float) $facture->montant, 2, ',', ' ').' MAD',
            ])
            ->all();

        foreach ($releve->payments as $payment) {
            $rows[] = [
                'date' => $payment->date_paiement?->format('d/m/Y'),
                'designation' => 'Paiement chèque '.$payment->numero_cheque,
                'montant' => '-'.number_format((float) $payment->montant, 2, ',', ' ').' MAD',
            ];
        }

        return FinancePdf::preview([
            'title' => 'Releve compte '.$releve->code_client,
            'subtitle' => 'Releve compte fournisseur',
            'brand' => 'Droguerie Palmeraie',
            'meta' => [
                'Fournisseur' => $fournisseur->nom,
                'Code client' => $releve->code_client,
                'Date releve' => $releve->date_releve?->format('d/m/Y'),
            ],
            'columns' => [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'designation', 'label' => 'Designation'],
                ['key' => 'montant', 'label' => 'Montant', 'align' => 'right'],
            ],
            'rows' => $rows,
            'summary' => [
                'Total releve compte' => number_format((float) ($releve->factures_sum_montant ?? 0), 2, ',', ' ').' MAD',
                'Total paiements' => number_format((float) ($releve->payments_sum_montant ?? 0), 2, ',', ' ').' MAD',
                'Reste' => number_format((float) (($releve->factures_sum_montant ?? 0) - ($releve->payments_sum_montant ?? 0)), 2, ',', ' ').' MAD',
            ],
            'note' => $releve->note,
        ], DownloadFilename::pdf('releve-compte', $fournisseur->nom, $releve->code_client, $releve->date_releve?->format('Y-m-d') ?: (string) $releve->id));
    }

    public function pdfPayment(Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurPayment $payment): Response
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id || $payment->fournisseur_releve_compte_id !== $releve->id, 404);

        return FinancePdf::preview([
            'title' => 'Paiement chèque '.$payment->numero_cheque,
            'subtitle' => 'Paiement fournisseur',
            'brand' => 'Droguerie Palmeraie',
            'meta' => [
                'Fournisseur' => $fournisseur->nom,
                'Code client' => $releve->code_client,
                'Date paiement' => $payment->date_paiement?->format('d/m/Y'),
            ],
            'columns' => [
                ['key' => 'numero', 'label' => 'N cheque'],
                ['key' => 'banque', 'label' => 'Banque'],
                ['key' => 'echeance', 'label' => 'Echeance'],
                ['key' => 'montant', 'label' => 'Montant', 'align' => 'right'],
            ],
            'rows' => [[
                'numero' => $payment->numero_cheque,
                'banque' => $payment->banque,
                'echeance' => $payment->date_echeance?->format('d/m/Y'),
                'montant' => number_format((float) $payment->montant, 2, ',', ' ').' MAD',
            ]],
            'note' => $payment->note,
        ], DownloadFilename::pdf('paiement-fournisseur', $fournisseur->nom, $payment->numero_cheque ?: (string) $payment->id, $payment->date_paiement?->format('Y-m-d') ?: 'date'));
    }

    public function chequeRows(Fournisseur $fournisseur, array $filters): LengthAwarePaginator
    {
        return $fournisseur->cheques()
            ->when($filters['cheque_statut'] ?? null, fn ($query, $value) => $query->where('statut', $value))
            ->when($filters['cheque_banque'] ?? null, fn ($query, $value) => $query->where('banque', 'like', "%{$value}%"))
            ->when($filters['cheque_date_from'] ?? null, fn ($query, $value) => $query->whereDate('date_echeance', '>=', $value))
            ->when($filters['cheque_date_to'] ?? null, fn ($query, $value) => $query->whereDate('date_echeance', '<=', $value))
            ->latest('date_echeance')
            ->paginate(100, ['*'], 'cheques_page')
            ->withQueryString()
            ->through(fn (FournisseurCheque $cheque) => $this->serializeCheque($cheque));
    }

    public function syncChequePayment(FournisseurCheque $cheque): void
    {
        // Payments now belong to a selected relevé compte, not directly to a standalone cheque.
    }

    public function setChequeStatus(FournisseurCheque $cheque, string $statut): void
    {
        DB::transaction(function () use ($cheque, $statut): void {
            $cheque->update(['statut' => $statut]);

            $cheque->payment()->delete();
        });
    }

    private function baseQuery(array $filters): Builder
    {
        return Fournisseur::query()
            ->withSum('factures', 'montant')
            ->withSum('payments', 'montant')
            ->when($filters['search'] ?? null, function ($query, $value): void {
                $query->where(fn ($inner) => $inner
                    ->where('nom', 'like', "%{$value}%")
                    ->orWhere('telephone', 'like', "%{$value}%")
                    ->orWhere('ville', 'like', "%{$value}%"));
            })
            ->when($filters['ville'] ?? null, fn ($query, $value) => $query->where('ville', 'like', "%{$value}%"))
            ->when($filters['balance_min'] ?? null, fn ($query, $value) => $query->whereRaw($this->balanceSql().' >= ?', [$value]))
            ->when($filters['balance_max'] ?? null, fn ($query, $value) => $query->whereRaw($this->balanceSql().' <= ?', [$value]));
    }

    public function serialize(Fournisseur $fournisseur): array
    {
        $totalFactures = (float) ($fournisseur->factures_sum_montant ?? 0);
        $totalPaye = (float) ($fournisseur->payments_sum_montant ?? 0);

        return [
            'id' => $fournisseur->id,
            'nom' => $fournisseur->nom,
            'telephone' => $fournisseur->telephone,
            'ville' => $fournisseur->ville,
            'note' => $fournisseur->note,
            'total_factures' => round($totalFactures, 2),
            'total_paye' => round($totalPaye, 2),
            'balance' => round($totalFactures - $totalPaye, 2),
        ];
    }

    public function serializeReleve(FournisseurReleveCompte $releve): array
    {
        $totalFactures = (float) ($releve->factures_sum_montant ?? 0);
        $totalPayments = (float) ($releve->payments_sum_montant ?? 0);

        return [
            'id' => $releve->id,
            'code_client' => $releve->code_client,
            'date_releve' => $releve->date_releve?->format('Y-m-d'),
            'note' => $releve->note,
            'total_factures' => round($totalFactures, 2),
            'total_paye' => round($totalPayments, 2),
            'balance' => round($totalFactures - $totalPayments, 2),
        ];
    }

    public function serializeGlobalReleve(FournisseurReleveCompte $releve): array
    {
        return [
            ...$this->serializeReleve($releve),
            'fournisseur_id' => $releve->fournisseur_id,
            'fournisseur_nom' => $releve->fournisseur->nom,
        ];
    }

    private function balanceSql(): string
    {
        return '(select coalesce(sum(montant), 0) from fournisseur_factures where fournisseur_factures.fournisseur_id = fournisseurs.id) - (select coalesce(sum(montant), 0) from fournisseur_payments where fournisseur_payments.fournisseur_id = fournisseurs.id)';
    }

    public function serializeCheque(FournisseurCheque $cheque): array
    {
        return [
            'id' => $cheque->id,
            'type' => $cheque->type,
            'numero_cheque' => $cheque->numero_cheque,
            'banque' => $cheque->banque,
            'montant' => (float) $cheque->montant,
            'tireur_signataire' => $cheque->tireur_signataire,
            'motif' => $cheque->motif,
            'date_emission' => $cheque->date_emission?->format('Y-m-d'),
            'date_echeance' => $cheque->date_echeance?->format('Y-m-d'),
            'statut' => $cheque->statut,
            'note' => $cheque->note,
        ];
    }

    private function facturesQuery(FournisseurReleveCompte $releve, array $filters)
    {
        return $releve->factures()
            ->when($filters['facture_search'] ?? null, fn ($query, $value) => $query->where('numero_facture', 'like', "%{$value}%"))
            ->when($filters['facture_date_from'] ?? null, fn ($query, $value) => $query->whereDate('date_facture', '>=', $value))
            ->when($filters['facture_date_to'] ?? null, fn ($query, $value) => $query->whereDate('date_facture', '<=', $value));
    }

    private function relevesQuery(array $filters): Builder
    {
        return FournisseurReleveCompte::query()
            ->with('fournisseur:id,nom')
            ->withSum('factures', 'montant')
            ->withSum('payments', 'montant')
            ->when($filters['search'] ?? null, function (Builder $query, string $value): void {
                $query->where(function (Builder $inner) use ($value): void {
                    $inner->where('code_client', 'like', "%{$value}%")
                        ->orWhereHas('fournisseur', fn (Builder $supplier) => $supplier->where('nom', 'like', "%{$value}%"));
                });
            })
            ->when($filters['fournisseur_id'] ?? null, fn (Builder $query, $value) => $query->where('fournisseur_id', $value))
            ->when($filters['date_from'] ?? null, fn (Builder $query, $value) => $query->whereDate('date_releve', '>=', $value))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $value) => $query->whereDate('date_releve', '<=', $value));
    }

    private function paymentsQuery(FournisseurReleveCompte $releve, array $filters)
    {
        return $releve->payments()
            ->when($filters['payment_date_from'] ?? null, fn ($query, $value) => $query->whereDate('date_paiement', '>=', $value))
            ->when($filters['payment_date_to'] ?? null, fn ($query, $value) => $query->whereDate('date_paiement', '<=', $value))
            ->when($filters['payment_cheque'] ?? null, fn ($query, $value) => $query->where('numero_cheque', 'like', "%{$value}%"))
            ->when($filters['payment_banque'] ?? null, fn ($query, $value) => $query->where('banque', 'like', "%{$value}%"));
    }
}
