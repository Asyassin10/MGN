<?php

namespace App\Services;

use App\Models\ChequeClient;
use App\Models\Client;
use App\Models\ClientEntry;
use App\Models\ClientPayment;
use App\Support\DownloadFilename;
use App\Support\ExcelExport;
use App\Support\FinancePdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest()
            ->paginate(100)
            ->withQueryString()
            ->through(fn (Client $client) => $this->serialize($client));
    }

    public function topBalances(): array
    {
        return Client::query()
            ->withSum('entries', 'montant')
            ->withSum('payments', 'montant')
            ->withSum('cheques', 'montant')
            ->get()
            ->map(fn (Client $client) => $this->serialize($client))
            ->sortByDesc('balance')
            ->take(8)
            ->values()
            ->all();
    }

    public function summary(): array
    {
        $totalDu = (float) ClientEntry::query()->sum('montant');
        $totalPaye = (float) ClientPayment::query()->sum('montant') + (float) ChequeClient::query()->sum('montant');
        $today = today();

        return [
            'total_du' => round($totalDu, 2),
            'total_paye' => round($totalPaye, 2),
            'balance' => round($totalDu - $totalPaye, 2),
            'today_du' => round((float) ClientEntry::query()->whereDate('date_entree', $today)->sum('montant'), 2),
            'today_paye' => round((float) ClientPayment::query()->whereDate('date_paiement', $today)->sum('montant'), 2),
        ];
    }

    public function show(Client $client, array $filters): array
    {
        $client->loadSum('entries', 'montant')->loadSum('payments', 'montant')->loadSum('cheques', 'montant');
        $entriesQuery = $this->entriesQuery($client, $filters);
        $paymentsQuery = $this->paymentsQuery($client, $filters);

        return [
            'client' => $this->serialize($client),
            'entries' => $entriesQuery
                ->latest()
                ->paginate(100, ['*'], 'entries_page')
                ->withQueryString()
                ->through(fn (ClientEntry $entry) => $this->serializeEntry($entry)),
            'payments' => ['data' => $paymentsQuery->latest()->latest('id')->get()->map(fn (ClientPayment $payment) => $this->serializePayment($payment))->toBase()->merge($this->chequesQuery($client, $filters)->latest()->latest('id')->get()->map(fn (ChequeClient $cheque) => $this->serializeCheque($cheque))->toBase())->sortByDesc('sort_key')->values()->all(), 'links' => []],
        ];
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)
            ->when($filters['selected_ids'] ?? [], fn (Builder $query, array $ids) => $query->whereKey($ids))
            ->latest()
            ->get()
            ->map(fn (Client $client) => [
                $client->nom,
                $client->ville,
                $client->telephone,
                round((float) ($client->entries_sum_montant ?? 0), 2),
                round((float) ($client->payments_sum_montant ?? 0), 2),
                round(((float) ($client->entries_sum_montant ?? 0)) - ((float) ($client->payments_sum_montant ?? 0)), 2),
            ]);

        return ExcelExport::download('clients-export', ['Nom', 'Ville', 'Telephone', 'Total du', 'Total paye', 'Solde'], $rows);
    }

    public function exportEntries(Client $client, array $filters): StreamedResponse
    {
        $rows = $this->entriesQuery($client, $filters)
            ->when($filters['selected_ids'] ?? [], fn (Builder $query, array $ids) => $query->whereKey($ids))
            ->latest()
            ->get()
            ->map(fn (ClientEntry $entry) => [
                $entry->date_entree?->format('Y-m-d'),
                $entry->montant,
                $entry->description,
            ]);

        return ExcelExport::download('client-'.$client->id.'-entrees-export', ['Date', 'Montant', 'Description'], $rows);
    }

    public function exportPayments(Client $client, array $filters): StreamedResponse
    {
        $selectedIds = collect($filters['selected_ids'] ?? []);
        $paymentIds = $selectedIds->reject(fn ($id) => str_starts_with((string) $id, 'cheque-'))->map(fn ($id) => (int) $id)->all();
        $chequeIds = $selectedIds->filter(fn ($id) => str_starts_with((string) $id, 'cheque-'))->map(fn ($id) => (int) str_replace('cheque-', '', (string) $id))->all();

        $rows = $this->paymentsQuery($client, $filters)
            ->when($selectedIds->isNotEmpty(), fn (Builder $query) => $query->whereKey($paymentIds))
            ->latest()
            ->get()
            ->map(fn (ClientPayment $payment) => [
                $payment->date_paiement?->format('Y-m-d'),
                $payment->montant,
                $payment->mode,
                $payment->reference,
                $payment->note,
            ])->merge($this->chequesQuery($client, $filters)->when($selectedIds->isNotEmpty(), fn (Builder $query) => $query->whereKey($chequeIds))->latest()->get()->map(fn (ChequeClient $cheque) => [$cheque->date_emission?->format('Y-m-d'), $cheque->montant, $cheque->type, $cheque->numero_cheque, $cheque->banque, $cheque->statut]));

        return ExcelExport::download('client-'.$client->id.'-paiements-export', ['Date', 'Montant', 'Mode', 'Reference / Numero', 'Banque', 'Statut'], $rows);
    }

    public function pdfPayment(Client $client, ClientPayment $payment): Response
    {
        abort_if($payment->client_id !== $client->id, 404);

        return FinancePdf::preview([
            'title' => 'Paiement client '.$client->nom,
            'subtitle' => 'Paiement client',
            'brand' => 'Droguerie P',
            'meta' => [
                'Client' => $client->nom,
                'Date paiement' => $payment->date_paiement?->format('d/m/Y'),
                'Mode' => $payment->mode,
            ],
            'columns' => [
                ['key' => 'montant', 'label' => 'Montant', 'align' => 'right'],
                ['key' => 'reference', 'label' => 'Reference'],
                ['key' => 'note', 'label' => 'Note'],
            ],
            'rows' => [[
                'montant' => number_format((float) $payment->montant, 2, ',', ' ').' MAD',
                'reference' => $payment->reference ?: '-',
                'note' => $payment->note ?: '-',
            ]],
        ], DownloadFilename::pdf('paiement-client', $client->nom, $payment->reference ?: (string) $payment->id, $payment->date_paiement?->format('Y-m-d') ?: 'date'));
    }

    public function pdfReleve(Client $client, array $filters): Response
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $entries = $client->entries()
            ->when($dateFrom, fn ($query, $value) => $query->whereDate('date_entree', '>=', $value))
            ->when($dateTo, fn ($query, $value) => $query->whereDate('date_entree', '<=', $value))
            ->get();

        $payments = $client->payments()
            ->when($dateFrom, fn ($query, $value) => $query->whereDate('date_paiement', '>=', $value))
            ->when($dateTo, fn ($query, $value) => $query->whereDate('date_paiement', '<=', $value))
            ->get();

        $cheques = $client->cheques()
            ->when($dateFrom, fn ($query, $value) => $query->whereDate('date_emission', '>=', $value))
            ->when($dateTo, fn ($query, $value) => $query->whereDate('date_emission', '<=', $value))
            ->get();

        $rows = $entries->map(fn (ClientEntry $entry) => [
            'sort_date' => $entry->date_entree,
            'date' => $entry->date_entree?->format('d/m/Y'),
            'entree' => number_format((float) $entry->montant, 2, ',', ' ').' MAD',
            'paiement' => '-',
            'description' => $entry->description ?: '-',
        ])->concat($payments->map(fn (ClientPayment $payment) => [
            'sort_date' => $payment->date_paiement,
            'date' => $payment->date_paiement?->format('d/m/Y'),
            'entree' => '-',
            'paiement' => number_format((float) $payment->montant, 2, ',', ' ').' MAD',
            'description' => $payment->note ?: ($payment->reference ?: '-'),
        ]))->concat($cheques->map(fn (ChequeClient $cheque) => [
            'sort_date' => $cheque->date_emission,
            'date' => $cheque->date_emission?->format('d/m/Y'),
            'entree' => '-',
            'paiement' => number_format((float) $cheque->montant, 2, ',', ' ').' MAD',
            'description' => $cheque->motif ?: (ucfirst($cheque->type).' '.$cheque->numero_cheque),
        ]))->sortBy('sort_date')->values();

        $totalEntrees = (float) $entries->sum('montant');
        $totalPaiements = (float) $payments->sum('montant') + (float) $cheques->sum('montant');

        return FinancePdf::preview([
            'title' => 'Releve de compte '.$client->nom,
            'subtitle' => 'Releve compte client',
            'brand' => 'Droguerie P',
            'meta' => [
                'Client' => $client->nom,
                'Periode' => ($dateFrom ? \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') : 'Debut').' - '.($dateTo ? \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y') : "Aujourd'hui"),
            ],
            'columns' => [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'entree', 'label' => 'Entree', 'align' => 'right'],
                ['key' => 'paiement', 'label' => 'Paiement', 'align' => 'right'],
                ['key' => 'description', 'label' => 'Description'],
            ],
            'rows' => $rows->all(),
            'summary' => [
                'Total entrees' => number_format($totalEntrees, 2, ',', ' ').' MAD',
                'Total paiements' => number_format($totalPaiements, 2, ',', ' ').' MAD',
                'Solde' => number_format($totalEntrees - $totalPaiements, 2, ',', ' ').' MAD',
            ],
        ], DownloadFilename::pdf('releve-client', $client->nom, $dateFrom ?: 'debut', $dateTo ?: 'fin'));
    }

    private function baseQuery(array $filters): Builder
    {
        return Client::query()
            ->whereNull('source')
            ->withSum('entries', 'montant')
            ->withSum('payments', 'montant')
            ->withSum('cheques', 'montant')
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

    public function serialize(Client $client): array
    {
        $totalDu = (float) ($client->entries_sum_montant ?? 0);
        $totalPaye = (float) ($client->payments_sum_montant ?? 0) + (float) ($client->cheques_sum_montant ?? 0);

        return [
            'id' => $client->id,
            'nom' => $client->nom,
            'telephone' => $client->telephone,
            'ville' => $client->ville,
            'note' => $client->note,
            'total_du' => round($totalDu, 2),
            'total_paye' => round($totalPaye, 2),
            'balance' => round($totalDu - $totalPaye, 2),
        ];
    }

    private function balanceSql(): string
    {
        return '(select coalesce(sum(montant), 0) from client_entries where client_entries.client_id = clients.id) - (select coalesce(sum(montant), 0) from client_payments where client_payments.client_id = clients.id) - (select coalesce(sum(montant), 0) from cheque_clients where cheque_clients.client_id = clients.id)';
    }

    private function entriesQuery(Client $client, array $filters)
    {
        return $client->entries()
            ->when($filters['entry_date_from'] ?? null, fn ($query, $value) => $query->whereDate('date_entree', '>=', $value))
            ->when($filters['entry_date_to'] ?? null, fn ($query, $value) => $query->whereDate('date_entree', '<=', $value))
            ->when($filters['entry_min'] ?? null, fn ($query, $value) => $query->where('montant', '>=', $value))
            ->when($filters['entry_max'] ?? null, fn ($query, $value) => $query->where('montant', '<=', $value));
    }

    private function paymentsQuery(Client $client, array $filters)
    {
        return $client->payments()
            ->when($filters['payment_date_from'] ?? null, fn ($query, $value) => $query->whereDate('date_paiement', '>=', $value))
            ->when($filters['payment_date_to'] ?? null, fn ($query, $value) => $query->whereDate('date_paiement', '<=', $value))
            ->when($filters['payment_mode'] ?? null, fn ($query, $value) => $query->where('mode', $value))
            ->when($filters['payment_min'] ?? null, fn ($query, $value) => $query->where('montant', '>=', $value))
            ->when($filters['payment_max'] ?? null, fn ($query, $value) => $query->where('montant', '<=', $value));
    }

    private function serializeEntry(ClientEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'date_entree' => $entry->date_entree?->format('Y-m-d'),
            'montant' => (float) $entry->montant,
            'description' => $entry->description,
        ];
    }

    private function serializePayment(ClientPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'date_paiement' => $payment->date_paiement?->format('Y-m-d'),
            'montant' => (float) $payment->montant,
            'mode' => $payment->mode,
            'reference' => $payment->reference,
            'note' => $payment->note,
            'record_type' => 'payment',
            'sort_date' => $payment->date_paiement?->format('Y-m-d'),
            'sort_key' => sprintf('%s-%010d', $payment->created_at?->format('Y-m-d H:i:s') ?: '', $payment->id),
        ];
    }

    private function chequesQuery(Client $client, array $filters)
    {
        return $client->cheques()
            ->when($filters['payment_mode'] ?? null, fn ($query, $value) => in_array($value, ChequeClient::TYPES, true) ? $query->where('type', $value) : $query->whereRaw('1 = 0'))
            ->when($filters['payment_min'] ?? null, fn ($query, $value) => $query->where('montant', '>=', $value))
            ->when($filters['payment_max'] ?? null, fn ($query, $value) => $query->where('montant', '<=', $value));
    }

    private function serializeCheque(ChequeClient $cheque): array
    {
        return ['id' => 'cheque-'.$cheque->id, 'resource_id' => $cheque->id, 'record_type' => 'cheque', 'sort_date' => $cheque->date_emission?->format('Y-m-d') ?: $cheque->date_echeance?->format('Y-m-d'), 'sort_key' => sprintf('%s-%010d', $cheque->created_at?->format('Y-m-d H:i:s') ?: '', $cheque->id), 'date_paiement' => $cheque->date_emission?->format('Y-m-d'), 'montant' => (float) $cheque->montant, 'mode' => $cheque->type, 'reference' => $cheque->numero_cheque, 'numero_cheque' => $cheque->numero_cheque, 'banque' => $cheque->banque, 'tireur_signataire' => $cheque->tireur_signataire, 'date_emission' => $cheque->date_emission?->format('Y-m-d'), 'date_echeance' => $cheque->date_echeance?->format('Y-m-d'), 'statut' => $cheque->statut, 'facture_recue' => $cheque->facture_recue, 'facture_donnee' => $cheque->facture_donnee, 'note' => $cheque->motif];
    }
}
