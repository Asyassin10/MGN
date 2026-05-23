<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientEntry;
use App\Models\Depot;
use App\Models\Fournisseur;
use App\Models\FournisseurFacture;
use App\Models\Operation;
use App\Support\ArticleNameLookup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function data(): array
    {
        return [
            'cheques' => $this->chequeData(),
            'depot' => $this->depotData(),
            'fournisseurs' => $this->supplierData(),
            'clients' => $this->clientData(),
        ];
    }

    private function depotData(): array
    {
        $totalDepots = Depot::query()->count();
        $totalStock = (int) DB::table('depot_article')->sum('quantity');
        $totalOperations = Operation::query()->count();

        $stockByDepot = DB::table('depots')
            ->leftJoin('depot_article', 'depots.id', '=', 'depot_article.depot_id')
            ->select('depots.id', 'depots.name', DB::raw('COALESCE(SUM(depot_article.quantity), 0) as stock'), DB::raw('COUNT(depot_article.article_id) as articles'))
            ->groupBy('depots.id', 'depots.name')
            ->orderByDesc('stock')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'stock' => (int) $row->stock,
                'articles' => (int) $row->articles,
            ]);

        $operationTypeSplit = Operation::query()
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->get()
            ->map(fn ($row) => ['name' => $row->type === 'entree' ? 'Entrées' : 'Sorties', 'value' => (int) $row->total]);

        return [
            'kpis' => [
                'total_articles' => DB::table('articles')->count(),
                'total_depots' => $totalDepots,
                'assigned_articles' => DB::table('depot_article')->count(),
                'total_stock' => $totalStock,
                'average_stock_by_depot' => $totalDepots > 0 ? round($totalStock / $totalDepots, 2) : 0,
                'average_quantity_by_line' => round((float) (DB::table('depot_article')->avg('quantity') ?? 0), 2),
                'low_stock_count' => DB::table('depot_article')->where('quantity', '<', 5)->count(),
                'zero_stock_count' => DB::table('depot_article')->where('quantity', '<=', 0)->count(),
                'operations_total' => $totalOperations,
                'operations_this_month' => Operation::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'entries_total' => Operation::query()->where('type', 'entree')->count(),
                'exits_total' => Operation::query()->where('type', 'sortie')->count(),
            ],
            'stockByDepot' => $stockByDepot->values(),
            'articleDistributionByDepot' => $stockByDepot->map(fn ($row) => ['name' => $row['name'], 'value' => $row['articles']])->values(),
            'topStockedDepots' => $stockByDepot->take(5)->values(),
            'lowStockSeverity' => [
                ['name' => 'Rupture', 'value' => DB::table('depot_article')->where('quantity', '<=', 0)->count()],
                ['name' => 'Critique', 'value' => DB::table('depot_article')->whereBetween('quantity', [1, 2])->count()],
                ['name' => 'Faible', 'value' => DB::table('depot_article')->whereBetween('quantity', [3, 4])->count()],
            ],
            'operationTypeSplit' => $operationTypeSplit,
            'monthlyOperations' => $this->monthlyCounts(DB::table('operations')->get(['created_at', 'type'])),
            'lowStock' => DB::table('depot_article')
                ->join('articles', 'articles.id', '=', 'depot_article.article_id')
                ->join('depots', 'depots.id', '=', 'depot_article.depot_id')
                ->where('depot_article.quantity', '<', 5)
                ->select('articles.name', 'articles.reference', 'depots.name as depot', 'depot_article.quantity')
                ->orderBy('depot_article.quantity')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'reference' => $row->reference,
                    'name' => ArticleNameLookup::resolve((string) $row->reference, (string) $row->name),
                    'depot' => $row->depot,
                    'quantity' => (int) $row->quantity,
                ]),
            'recentOperations' => Operation::query()->with(['depot', 'employee', 'lines'])->latest()->take(8)->get()->map(fn ($operation) => [
                'id' => $operation->id,
                'reference' => $operation->reference,
                'type' => $operation->type,
                'depot' => $operation->depot?->name,
                'employee' => $operation->employee?->name,
                'lines_count' => $operation->lines->count(),
                'created_at' => $operation->created_at->format('Y-m-d H:i'),
            ]),
        ];
    }

    private function chequeData(): array
    {
        $records = $this->chequeRecords();
        $statusTotals = $records->groupBy('statut')->map(fn ($items) => round((float) $items->sum('montant'), 2));
        $typeTotals = $records->groupBy('tier')->map(fn ($items) => round((float) $items->sum('montant'), 2));

        return [
            'kpis' => [
                'count' => $records->count(),
                'total_amount' => round((float) $records->sum('montant'), 2),
                'en_cours' => (float) ($statusTotals['en_cours'] ?? 0),
                'en_caisse' => (float) ($statusTotals['en_caisse'] ?? 0),
                'impaye' => (float) ($statusTotals['impaye'] ?? 0),
                'client_count' => $records->where('tier', 'client')->count(),
                'fournisseur_count' => $records->where('tier', 'fournisseur')->count(),
                'average_amount' => $records->count() > 0 ? round((float) $records->avg('montant'), 2) : 0,
            ],
            'statusPie' => $this->pieFromMap($statusTotals, [
                'en_cours' => 'En cours',
                'en_caisse' => 'En caisse',
                'impaye' => 'Impayé',
            ]),
            'typePie' => $this->pieFromMap($typeTotals, [
                'client' => 'Clients',
                'fournisseur' => 'Fournisseurs',
            ]),
            'monthly' => $this->monthlyAmounts($records, 'date_echeance'),
            'topBanks' => $records
                ->filter(fn ($row) => filled($row['banque']))
                ->groupBy('banque')
                ->map(fn ($items, $bank) => ['name' => $bank, 'total' => round((float) $items->sum('montant'), 2), 'count' => $items->count()])
                ->sortByDesc('total')
                ->take(5)
                ->values(),
            'upcoming' => $records
                ->filter(fn ($row) => filled($row['date_echeance']))
                ->sortBy('date_echeance')
                ->take(8)
                ->values(),
        ];
    }

    private function supplierData(): array
    {
        $due = (float) DB::table('fournisseur_factures')->sum('montant');
        $paid = (float) DB::table('fournisseur_payments')->sum('montant');

        $factureTotals = DB::table('fournisseur_factures')
            ->select('fournisseur_id', DB::raw('SUM(montant) as total_du'))
            ->groupBy('fournisseur_id');

        $paymentTotals = DB::table('fournisseur_payments')
            ->select('fournisseur_id', DB::raw('SUM(montant) as total_paye'))
            ->groupBy('fournisseur_id');

        $balances = DB::table('fournisseurs')
            ->leftJoinSub($factureTotals, 'facture_totals', 'fournisseurs.id', '=', 'facture_totals.fournisseur_id')
            ->leftJoinSub($paymentTotals, 'payment_totals', 'fournisseurs.id', '=', 'payment_totals.fournisseur_id')
            ->whereNull('fournisseurs.deleted_at')
            ->select('fournisseurs.id', 'fournisseurs.nom', DB::raw('COALESCE(facture_totals.total_du, 0) as total_du'), DB::raw('COALESCE(payment_totals.total_paye, 0) as total_paye'))
            ->get()
            ->map(fn ($row) => [
                'nom' => $row->nom,
                'total_du' => (float) $row->total_du,
                'total_paye' => (float) $row->total_paye,
                'balance' => round((float) $row->total_du - (float) $row->total_paye, 2),
            ])
            ->sortByDesc('balance')
            ->values();

        return [
            'kpis' => [
                'count' => Fournisseur::query()->count(),
                'releves_count' => DB::table('fournisseur_releve_comptes')->count(),
                'factures_count' => DB::table('fournisseur_factures')->count(),
                'payments_count' => DB::table('fournisseur_payments')->count(),
                'total_du' => $due,
                'total_paye' => $paid,
                'balance' => round($due - $paid, 2),
                'average_facture' => round((float) (DB::table('fournisseur_factures')->avg('montant') ?? 0), 2),
            ],
            'top' => $balances->take(5)->values(),
            'paidVsDuePie' => [
                ['name' => 'Payé', 'value' => round($paid, 2)],
                ['name' => 'Reste', 'value' => max(round($due - $paid, 2), 0)],
            ],
            'monthlyFactures' => $this->monthlyAmounts(DB::table('fournisseur_factures')->get(['date_facture', 'montant']), 'date_facture'),
            'recentFactures' => FournisseurFacture::query()
                ->with('fournisseur')
                ->latest('date_facture')
                ->take(8)
                ->get()
                ->map(fn (FournisseurFacture $facture) => [
                    'id' => $facture->id,
                    'numero_facture' => $facture->numero_facture,
                    'fournisseur' => $facture->fournisseur?->nom,
                    'date_facture' => $facture->date_facture?->format('Y-m-d'),
                    'montant' => (float) $facture->montant,
                ]),
        ];
    }

    private function clientData(): array
    {
        $due = (float) DB::table('client_entries')->sum('montant');
        $paid = (float) DB::table('client_payments')->sum('montant');

        $entryTotals = DB::table('client_entries')
            ->select('client_id', DB::raw('SUM(montant) as total_du'))
            ->groupBy('client_id');

        $paymentTotals = DB::table('client_payments')
            ->select('client_id', DB::raw('SUM(montant) as total_paye'))
            ->groupBy('client_id');

        $balances = DB::table('clients')
            ->leftJoinSub($entryTotals, 'entry_totals', 'clients.id', '=', 'entry_totals.client_id')
            ->leftJoinSub($paymentTotals, 'payment_totals', 'clients.id', '=', 'payment_totals.client_id')
            ->whereNull('clients.deleted_at')
            ->select('clients.id', 'clients.nom', DB::raw('COALESCE(entry_totals.total_du, 0) as total_du'), DB::raw('COALESCE(payment_totals.total_paye, 0) as total_paye'))
            ->get()
            ->map(fn ($row) => [
                'nom' => $row->nom,
                'total_du' => (float) $row->total_du,
                'total_paye' => (float) $row->total_paye,
                'balance' => round((float) $row->total_du - (float) $row->total_paye, 2),
            ])
            ->sortByDesc('balance')
            ->values();

        return [
            'kpis' => [
                'count' => Client::query()->count(),
                'entries_count' => DB::table('client_entries')->count(),
                'payments_count' => DB::table('client_payments')->count(),
                'total_du' => $due,
                'total_encaisse' => $paid,
                'balance' => round($due - $paid, 2),
                'average_entry' => round((float) (DB::table('client_entries')->avg('montant') ?? 0), 2),
            ],
            'top' => $balances->take(5)->values(),
            'paidVsDuePie' => [
                ['name' => 'Encaissé', 'value' => round($paid, 2)],
                ['name' => 'Reste', 'value' => max(round($due - $paid, 2), 0)],
            ],
            'monthlyEntries' => $this->monthlyAmounts(DB::table('client_entries')->get(['date_entree', 'montant']), 'date_entree'),
            'recentEntries' => ClientEntry::query()
                ->with('client')
                ->latest('date_entree')
                ->take(8)
                ->get()
                ->map(fn (ClientEntry $entry) => [
                    'id' => $entry->id,
                    'client' => $entry->client?->nom,
                    'date_entree' => $entry->date_entree?->format('Y-m-d'),
                    'montant' => (float) $entry->montant,
                    'description' => $entry->description,
                ]),
        ];
    }

    private function chequeRecords(): Collection
    {
        $standalone = DB::table('cheques')
            ->whereNull('deleted_at')
            ->get(['numero_cheque', 'type', 'statut', 'banque', 'montant', 'date_echeance', 'created_at'])
            ->map(fn ($row) => [
                'numero_cheque' => $row->numero_cheque,
                'tier' => $row->type,
                'statut' => $row->statut === 'encaisse' ? 'en_caisse' : $row->statut,
                'banque' => $row->banque,
                'montant' => (float) $row->montant,
                'date_echeance' => $row->date_echeance,
                'created_at' => $row->created_at,
                'source' => 'Standalone',
            ]);

        $clients = DB::table('cheque_clients')
            ->get(['numero_cheque', 'statut', 'banque', 'montant', 'date_echeance', 'created_at'])
            ->map(fn ($row) => [
                'numero_cheque' => $row->numero_cheque,
                'tier' => 'client',
                'statut' => $row->statut,
                'banque' => $row->banque,
                'montant' => (float) $row->montant,
                'date_echeance' => $row->date_echeance,
                'created_at' => $row->created_at,
                'source' => 'Clients',
            ]);

        $fournisseurs = DB::table('cheque_fournisseurs')
            ->get(['numero_cheque', 'statut', 'banque', 'montant', 'date_echeance', 'created_at'])
            ->map(fn ($row) => [
                'numero_cheque' => $row->numero_cheque,
                'tier' => 'fournisseur',
                'statut' => $row->statut,
                'banque' => $row->banque,
                'montant' => (float) $row->montant,
                'date_echeance' => $row->date_echeance,
                'created_at' => $row->created_at,
                'source' => 'Fournisseurs',
            ]);

        return $standalone->merge($clients)->merge($fournisseurs)->values();
    }

    private function monthlyAmounts(Collection $rows, string $dateKey): Collection
    {
        return $rows
            ->filter(fn ($row) => filled(data_get($row, $dateKey)))
            ->groupBy(fn ($row) => substr((string) data_get($row, $dateKey), 0, 7))
            ->map(fn ($items, $month) => ['month' => $month, 'total' => round((float) $items->sum('montant'), 2)])
            ->sortBy('month')
            ->values();
    }

    private function monthlyCounts(Collection $rows): Collection
    {
        return $rows
            ->filter(fn ($row) => filled($row->created_at))
            ->groupBy(fn ($row) => substr((string) $row->created_at, 0, 7))
            ->map(fn ($items, $month) => [
                'month' => $month,
                'total' => $items->count(),
                'entrees' => $items->where('type', 'entree')->count(),
                'sorties' => $items->where('type', 'sortie')->count(),
            ])
            ->sortBy('month')
            ->values();
    }

    private function pieFromMap(Collection $map, array $labels): Collection
    {
        return collect($labels)
            ->map(fn ($label, $key) => ['name' => $label, 'value' => (float) ($map[$key] ?? 0)])
            ->filter(fn ($row) => $row['value'] > 0)
            ->values();
    }
}
