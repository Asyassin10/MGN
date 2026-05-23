<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientEntry;
use App\Models\ClientPayment;
use App\Support\ExcelExport;
use App\Support\FinancePdf;
use App\Support\DownloadFilename;
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
            ->get()
            ->map(fn (Client $client) => $this->serialize($client))
            ->sortByDesc('balance')
            ->take(8)
            ->values()
            ->all();
    }

    public function show(Client $client, array $filters): array
    {
        $client->loadSum('entries', 'montant')->loadSum('payments', 'montant');
        $entriesQuery = $this->entriesQuery($client, $filters);
        $paymentsQuery = $this->paymentsQuery($client, $filters);

        return [
            'client' => $this->serialize($client),
            'entries' => $entriesQuery
                ->latest('date_entree')
                ->paginate(100, ['*'], 'entries_page')
                ->withQueryString()
                ->through(fn (ClientEntry $entry) => $this->serializeEntry($entry)),
            'payments' => $paymentsQuery
                ->latest('date_paiement')
                ->paginate(100, ['*'], 'payments_page')
                ->withQueryString()
                ->through(fn (ClientPayment $payment) => $this->serializePayment($payment)),
        ];
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)
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
            ->latest('date_entree')
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
        $rows = $this->paymentsQuery($client, $filters)
            ->latest('date_paiement')
            ->get()
            ->map(fn (ClientPayment $payment) => [
                $payment->date_paiement?->format('Y-m-d'),
                $payment->montant,
                $payment->mode,
                $payment->reference,
                $payment->note,
            ]);

        return ExcelExport::download('client-'.$client->id.'-paiements-export', ['Date', 'Montant', 'Mode', 'Reference', 'Note'], $rows);
    }

    public function pdfPayment(Client $client, ClientPayment $payment): Response
    {
        abort_if($payment->client_id !== $client->id, 404);

        return FinancePdf::download([
            'title' => 'Paiement client '.$client->nom,
            'subtitle' => 'Paiement client',
            'brand' => 'Droguerie Palmeraie',
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

    private function baseQuery(array $filters): Builder
    {
        return Client::query()
            ->withSum('entries', 'montant')
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

    public function serialize(Client $client): array
    {
        $totalDu = (float) ($client->entries_sum_montant ?? 0);
        $totalPaye = (float) ($client->payments_sum_montant ?? 0);

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
        return '(select coalesce(sum(montant), 0) from client_entries where client_entries.client_id = clients.id) - (select coalesce(sum(montant), 0) from client_payments where client_payments.client_id = clients.id)';
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
        ];
    }
}
