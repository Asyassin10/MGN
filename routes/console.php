<?php

use App\Services\ChequeMaturityService;
use App\Services\ClientOverdueService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cheques:mark-due-in-caisse', function (ChequeMaturityService $service) {
    $counts = $service->markDueChequesInCaisse();
    $total = array_sum($counts);

    $this->info("{$total} chèque(s) mis en caisse.");
    $this->table(
        ['Module', 'Chèques mis à jour'],
        collect($counts)->map(fn (int $count, string $module) => [$module, $count])->values()->all(),
    );
})->purpose('Move due cheques from en_cours to en_caisse once their échéance date arrives.')
    ->dailyAt('00:05')
    ->timezone('Africa/Casablanca')
    ->withoutOverlapping();

Artisan::command('clients:check-overdue', function (ClientOverdueService $service) {
    $clients = $service->overdueClients();

    $this->info("{$clients->count()} client(s) en retard de plus de 30 jours.");
})->purpose('Check clients with an outstanding balance for more than 30 days.')
    ->dailyAt('00:10')
    ->timezone('Africa/Casablanca')
    ->withoutOverlapping();
