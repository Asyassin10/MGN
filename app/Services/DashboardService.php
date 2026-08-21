<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientEntry;
use App\Models\Cheque;
use App\Models\Depot;
use App\Models\Fournisseur;
use App\Models\FournisseurFacture;
use App\Models\Operation;
use App\Support\ArticleNameLookup;
use App\Support\ExcelExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardService
{
    public function __construct(private ClientOverdueService $clientOverdueService) {}

    public function data(): array
    {
        return [
            'global' => $this->globalData(),
            'depot' => $this->depotData(),
            'fournisseurs' => $this->supplierData(),
            'clients' => $this->clientData(),
        ];
    }

    public function exportOverdueClients(): StreamedResponse
    {
        $rows = $this->clientOverdueService->overdueClients()->map(fn (array $client) => [
            $client['nom'],
            $client['telephone'],
            $client['ville'],
            $client['oldest_entry_date'],
            $client['days_overdue'],
            $client['balance'],
        ]);

        return ExcelExport::download('clients-en-retard-export', ['Client', 'Téléphone', 'Ville', 'Plus ancienne entrée', 'Jours en retard', 'À recevoir DH'], $rows);
    }

    private function globalData(): array
    {
        $overdueClients = $this->clientOverdueService->overdueClients();
        $supplierDue = (float) DB::table('fournisseur_factures')->sum('montant');
        $supplierPaid = (float) DB::table('fournisseur_cheques')->sum('montant');
        $clientDue = (float) DB::table('client_entries')->sum('montant');
        $clientPaid = (float) DB::table('client_payments')->sum('montant')
            + (float) DB::table('cheque_clients')->sum('montant');
        $supplierChequesPending = DB::table('fournisseur_cheques')->where('statut', '!=', 'en_caisse');
        $availableCheques = Cheque::query()->where('est_sorti', false);
        $supplierBalance = max(round($supplierDue - $supplierPaid, 2), 0);
        $clientBalance = max(round($clientDue - $clientPaid, 2), 0);
        $availableChequeCount = $availableCheques->count();
        $availableChequeTotal = (float) $availableCheques->sum('montant');

        return [
            'kpis' => [
                'fournisseurs_count' => Fournisseur::query()->count(),
                'stock_total' => (int) DB::table('depot_article')->sum('quantity'),
                'fournisseurs_reste' => $supplierBalance,
                'clients_reste' => $clientBalance,
                'cheques_fournisseurs_en_attente_count' => $supplierChequesPending->count(),
                'cheques_fournisseurs_en_attente_total' => (float) $supplierChequesPending->sum('montant'),
                'cheques_disponibles_count' => $availableChequeCount,
                'cheques_disponibles_total' => $availableChequeTotal,
                'clients_overdue_count' => $overdueClients->count(),
            ],
            'comparison' => [
                ['name' => 'Reste à payer fournisseurs', 'value' => $supplierBalance, 'color' => '#dc2626'],
                ['name' => 'Reste à recevoir des clients', 'value' => $clientBalance, 'color' => '#059669'],
                ['name' => 'Chèques non sortis disponibles', 'value' => $availableChequeTotal, 'color' => '#7c3aed'],
            ],
            'overdue_clients' => $overdueClients,
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

    private function supplierData(): array
    {
        $due = (float) DB::table('fournisseur_factures')->sum('montant');
        $paid = (float) DB::table('fournisseur_cheques')->sum('montant');

        $factureTotals = DB::table('fournisseur_factures')
            ->select('fournisseur_id', DB::raw('SUM(montant) as total_du'))
            ->groupBy('fournisseur_id');

        $paymentTotals = DB::table('fournisseur_cheques')
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
                'payments_count' => DB::table('fournisseur_cheques')->count(),
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
        $paid = (float) DB::table('client_payments')->sum('montant')
            + (float) DB::table('cheque_clients')->sum('montant');

        $entryTotals = DB::table('client_entries')
            ->select('client_id', DB::raw('SUM(montant) as total_du'))
            ->groupBy('client_id');

        $paymentTotals = DB::table('client_payments')
            ->select('client_id', DB::raw('SUM(montant) as total_paye'))
            ->groupBy('client_id');

        $chequeTotals = DB::table('cheque_clients')
            ->select('client_id', DB::raw('SUM(montant) as total_cheques'))
            ->groupBy('client_id');

        $balances = DB::table('clients')
            ->leftJoinSub($entryTotals, 'entry_totals', 'clients.id', '=', 'entry_totals.client_id')
            ->leftJoinSub($paymentTotals, 'payment_totals', 'clients.id', '=', 'payment_totals.client_id')
            ->leftJoinSub($chequeTotals, 'cheque_totals', 'clients.id', '=', 'cheque_totals.client_id')
            ->whereNull('clients.deleted_at')
            ->select('clients.id', 'clients.nom', DB::raw('COALESCE(entry_totals.total_du, 0) as total_du'), DB::raw('COALESCE(payment_totals.total_paye, 0) + COALESCE(cheque_totals.total_cheques, 0) as total_paye'))
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
}
