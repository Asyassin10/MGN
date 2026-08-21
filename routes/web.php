<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\ChequeImpayeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard')->name('dashboard');

    Route::middleware('permission:depots')->group(function (): void {
        Route::resource('depots', DepotController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::resource('articles', ArticleController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('/depots/{depot}/adjust-stock', [DepotController::class, 'adjustStock'])->name('depots.adjust-stock');
        Route::resource('operations', OperationController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('/operations/{operation}', [OperationController::class, 'show'])->name('operations.show');
        Route::get('/operations/{operation}/pdf', [OperationController::class, 'pdf'])->name('operations.pdf');
    });
    Route::middleware('permission:employees')->group(function (): void {
        Route::resource('employees', EmployeeController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::get('/employees/{employee}/payments', [EmployeeController::class, 'paymentHistory'])->name('employees.payments.index');
        Route::get('/employees/{employee}/absences', [EmployeeController::class, 'absenceHistory'])->name('employees.absences.index');
        Route::post('/employees/{employee}/work-days', [EmployeeController::class, 'storeWorkDay'])->name('employees.work-days.store');
        Route::post('/employees/{employee}/absences', [EmployeeController::class, 'storeAbsence'])->name('employees.absences.store');
        Route::post('/employees/{employee}/salary-payments', [EmployeeController::class, 'storeSalaryPayment'])->name('employees.salary-payments.store');
    });
    Route::middleware('permission:cheques')->group(function (): void {
        Route::get('/cheques/impayes', [ChequeImpayeController::class, 'index'])->name('cheques.impayes.index');
        Route::post('/cheques/impayes', [ChequeImpayeController::class, 'store'])->name('cheques.impayes.store');
        Route::patch('/cheques/impayes/{chequeImpaye}/payer', [ChequeImpayeController::class, 'pay'])->name('cheques.impayes.pay');
        Route::patch('/cheques/impayes/{chequeImpaye}', [ChequeImpayeController::class, 'update'])->name('cheques.impayes.update');
        Route::delete('/cheques/impayes/{chequeImpaye}', [ChequeImpayeController::class, 'destroy'])->name('cheques.impayes.destroy');
        Route::get('/cheques', [ChequeController::class, 'index'])->name('cheques.index');
        Route::post('/cheques', [ChequeController::class, 'store'])->name('cheques.store');
        Route::patch('/cheques/{cheque}/sortie', [ChequeController::class, 'updateSortie'])->name('cheques.sortie');
        Route::patch('/cheques/{cheque}/inline', [ChequeController::class, 'updateInline'])->name('cheques.inline');
        Route::patch('/cheques/{cheque}', [ChequeController::class, 'update'])->name('cheques.update');
        Route::delete('/cheques/{cheque}', [ChequeController::class, 'destroy'])->name('cheques.destroy');
    });
    Route::middleware('permission:admin')->group(function (): void {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/database-backup', [DatabaseBackupController::class, 'download'])->name('settings.database-backup.download');
        Route::patch('/settings/pin', [SettingsController::class, 'updatePin'])->name('settings.pin.update');
        Route::post('/settings/banks', [SettingsController::class, 'storeBank'])->name('settings.banks.store');
        Route::patch('/settings/banks/{bank}', [SettingsController::class, 'updateBank'])->name('settings.banks.update');
        Route::delete('/settings/banks/{bank}', [SettingsController::class, 'destroyBank'])->name('settings.banks.destroy');
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/activity-history', [ActivityLogController::class, 'index'])->name('activity-history.index');
    });

    Route::middleware('permission:fournisseurs')->group(function (): void {
        Route::get('/fournisseurs/releves', [FournisseurController::class, 'relevesIndex'])->name('fournisseurs.releves.index');
        Route::resource('fournisseurs', FournisseurController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
        Route::post('/fournisseurs/{fournisseur}/releves', [FournisseurController::class, 'storeReleve'])->name('fournisseurs.releves.store');
        Route::get('/fournisseurs/{fournisseur}/releves/{releve}', [FournisseurController::class, 'showReleve'])->name('fournisseurs.releves.show');
        Route::patch('/fournisseurs/{fournisseur}/releves/{releve}', [FournisseurController::class, 'updateReleve'])->name('fournisseurs.releves.update');
        Route::delete('/fournisseurs/{fournisseur}/releves/{releve}', [FournisseurController::class, 'destroyReleve'])->name('fournisseurs.releves.destroy');
        Route::get('/fournisseurs/{fournisseur}/releves/{releve}/pdf', [FournisseurController::class, 'pdfReleve'])->name('fournisseurs.releves.pdf');
        Route::post('/fournisseurs/{fournisseur}/releves/{releve}/factures', [FournisseurController::class, 'storeReleveFacture'])->name('fournisseurs.releves.factures.store');
        Route::patch('/fournisseurs/{fournisseur}/releves/{releve}/factures/{facture}', [FournisseurController::class, 'updateFacture'])->name('fournisseurs.releves.factures.update');
        Route::delete('/fournisseurs/{fournisseur}/releves/{releve}/factures/{facture}', [FournisseurController::class, 'destroyFacture'])->name('fournisseurs.releves.factures.destroy');
        Route::post('/fournisseurs/{fournisseur}/releves/{releve}/payments', [FournisseurController::class, 'storeRelevePayment'])->name('fournisseurs.releves.payments.store');
        Route::get('/fournisseurs/{fournisseur}/releves/{releve}/payments/{payment}/pdf', [FournisseurController::class, 'pdfPayment'])->name('fournisseurs.releves.payments.pdf');
        Route::patch('/fournisseurs/{fournisseur}/releves/{releve}/payments/{payment}', [FournisseurController::class, 'updatePayment'])->name('fournisseurs.releves.payments.update');
        Route::delete('/fournisseurs/{fournisseur}/releves/{releve}/payments/{payment}', [FournisseurController::class, 'destroyPayment'])->name('fournisseurs.releves.payments.destroy');
        Route::post('/fournisseurs/{fournisseur}/factures', [FournisseurController::class, 'storeFacture'])->name('fournisseurs.factures.store');
        Route::patch('/fournisseurs/{fournisseur}/releves/{releve}/cheques/{cheque}/status', [FournisseurController::class, 'updateChequeStatus'])->name('fournisseurs.releves.cheques.status');
    });

    Route::middleware('permission:clients')->group(function (): void {
        Route::resource('clients', ClientController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
        Route::post('/clients/{client}/entries', [ClientController::class, 'storeEntry'])->name('clients.entries.store');
        Route::patch('/clients/{client}/entries/{entry}', [ClientController::class, 'updateEntry'])->name('clients.entries.update');
        Route::delete('/clients/{client}/entries/{entry}', [ClientController::class, 'destroyEntry'])->name('clients.entries.destroy');
        Route::post('/clients/{client}/payments', [ClientController::class, 'storePayment'])->name('clients.payments.store');
        Route::get('/clients/{client}/payments/{payment}/pdf', [ClientController::class, 'pdfPayment'])->name('clients.payments.pdf');
        Route::patch('/clients/{client}/payments/{payment}', [ClientController::class, 'updatePayment'])->name('clients.payments.update');
        Route::delete('/clients/{client}/payments/{payment}', [ClientController::class, 'destroyPayment'])->name('clients.payments.destroy');
        Route::post('/clients/{client}/cheques', [ClientController::class, 'storeCheque'])->name('clients.cheques.store');
        Route::patch('/clients/{client}/cheques/{cheque}', [ClientController::class, 'updateCheque'])->name('clients.cheques.update');
        Route::patch('/clients/{client}/cheques/{cheque}/status', [ClientController::class, 'updateChequeStatus'])->name('clients.cheques.status');
        Route::delete('/clients/{client}/cheques/{cheque}', [ClientController::class, 'destroyCheque'])->name('clients.cheques.destroy');
    });

});
