<?php

namespace App\Services;

use App\Models\ChequeClient;
use App\Models\Cheque;
use App\Models\FournisseurCheque;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ChequeMaturityService
{
    public function markDueChequesInCaisse(?CarbonInterface $date = null): array
    {
        $date = ($date ?? Carbon::today())->toDateString();

        return [
            'cheques' => Cheque::query()
                ->where('statut', 'en_cours')
                ->whereDate('date_echeance', '<=', $date)
                ->update(['statut' => 'en_caisse']),
            'cheque_clients' => ChequeClient::query()
                ->where('statut', 'en_cours')
                ->whereDate('date_echeance', '<=', $date)
                ->update(['statut' => 'en_caisse']),
            'fournisseur_cheques' => FournisseurCheque::query()
                ->where('statut', 'en_cours')
                ->whereDate('date_echeance', '<=', $date)
                ->update(['statut' => 'en_caisse']),
        ];
    }
}
