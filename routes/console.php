<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\ChequeMaturityService;

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
