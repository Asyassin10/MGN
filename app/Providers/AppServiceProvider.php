<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\ChequeClient;
use App\Models\Cheque;
use App\Models\ChequeImpaye;
use App\Models\Client;
use App\Models\ClientEntry;
use App\Models\ClientPayment;
use App\Models\Depot;
use App\Models\Employee;
use App\Models\EmployeeAbsence;
use App\Models\EmployeeSalaryPayment;
use App\Models\EmployeeWorkDay;
use App\Models\Fournisseur;
use App\Models\FournisseurCheque;
use App\Models\FournisseurFacture;
use App\Models\FournisseurReleveCompte;
use App\Models\Operation;
use App\Models\OperationLine;
use App\Models\User;
use App\Observers\ActivityLogObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Article::class, Cheque::class, ChequeImpaye::class, ChequeClient::class, Client::class, ClientEntry::class, ClientPayment::class, Depot::class, Employee::class, EmployeeAbsence::class, EmployeeSalaryPayment::class, EmployeeWorkDay::class, Fournisseur::class, FournisseurCheque::class, FournisseurFacture::class, FournisseurReleveCompte::class, Operation::class, OperationLine::class, User::class] as $model) {
            $model::observe(ActivityLogObserver::class);
        }
    }
}
