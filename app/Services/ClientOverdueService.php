<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientOverdueService
{
    public function overdueClients(?CarbonInterface $date = null): Collection
    {
        $date = ($date ?? Carbon::today())->startOfDay();
        $entries = DB::table('client_entries')
            ->select('client_id', DB::raw('SUM(montant) as total_du'), DB::raw('MIN(date_entree) as oldest_entry_date'))
            ->groupBy('client_id');
        $payments = DB::table('client_payments')
            ->select('client_id', DB::raw('SUM(montant) as total_paye'))
            ->groupBy('client_id');
        $cheques = DB::table('cheque_clients')
            ->select('client_id', DB::raw('SUM(montant) as total_cheques'))
            ->groupBy('client_id');

        return DB::table('clients')
            ->joinSub($entries, 'entries', 'clients.id', '=', 'entries.client_id')
            ->leftJoinSub($payments, 'payments', 'clients.id', '=', 'payments.client_id')
            ->leftJoinSub($cheques, 'cheques', 'clients.id', '=', 'cheques.client_id')
            ->whereNull('clients.deleted_at')
            ->select('clients.id', 'clients.nom', 'clients.telephone', 'clients.ville', 'entries.oldest_entry_date', DB::raw('COALESCE(entries.total_du, 0) - COALESCE(payments.total_paye, 0) - COALESCE(cheques.total_cheques, 0) as balance'))
            ->get()
            ->map(function ($client) use ($date) {
                $oldestEntryDate = Carbon::parse($client->oldest_entry_date)->startOfDay();

                return [
                    'id' => $client->id,
                    'nom' => $client->nom,
                    'telephone' => $client->telephone,
                    'ville' => $client->ville,
                    'oldest_entry_date' => $oldestEntryDate->toDateString(),
                    'days_overdue' => $oldestEntryDate->isBefore($date) ? $oldestEntryDate->diffInDays($date) : 0,
                    'balance' => round((float) $client->balance, 2),
                ];
            })
            ->filter(fn (array $client) => $client['balance'] > 0 && $client['days_overdue'] > 30)
            ->sortByDesc('days_overdue')
            ->values();
    }
}
