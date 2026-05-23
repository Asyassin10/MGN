<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\ChequeClientController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\ChequeFournisseurController;
use App\Http\Controllers\ChequePartyClientController;
use App\Http\Controllers\ChequePartyFournisseurController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('depots', DepotController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::resource('articles', ArticleController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/depots/{depot}/adjust-stock', [DepotController::class, 'adjustStock'])->name('depots.adjust-stock');
    Route::resource('operations', OperationController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('/operations/{operation}', [OperationController::class, 'show'])->name('operations.show');
    Route::get('/operations/{operation}/pdf', [OperationController::class, 'pdf'])->name('operations.pdf');
    Route::resource('employees', EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/pin', [SettingsController::class, 'updatePin'])->name('settings.pin.update');

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
    Route::post('/fournisseurs/{fournisseur}/payments', [FournisseurController::class, 'storePayment'])->name('fournisseurs.payments.store');
    Route::post('/fournisseurs/{fournisseur}/cheques', [FournisseurController::class, 'storeCheque'])->name('fournisseurs.cheques.store');
    Route::patch('/fournisseurs/{fournisseur}/cheques/{cheque}', [FournisseurController::class, 'updateCheque'])->name('fournisseurs.cheques.update');
    Route::patch('/fournisseur-cheques/{cheque}/status', [FournisseurController::class, 'updateChequeStatus'])->name('fournisseur-cheques.status');
    Route::delete('/fournisseurs/{fournisseur}/cheques/{cheque}', [FournisseurController::class, 'destroyCheque'])->name('fournisseurs.cheques.destroy');

    Route::resource('clients', ClientController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
    Route::post('/clients/{client}/entries', [ClientController::class, 'storeEntry'])->name('clients.entries.store');
    Route::patch('/clients/{client}/entries/{entry}', [ClientController::class, 'updateEntry'])->name('clients.entries.update');
    Route::delete('/clients/{client}/entries/{entry}', [ClientController::class, 'destroyEntry'])->name('clients.entries.destroy');
    Route::post('/clients/{client}/payments', [ClientController::class, 'storePayment'])->name('clients.payments.store');
    Route::get('/clients/{client}/payments/{payment}/pdf', [ClientController::class, 'pdfPayment'])->name('clients.payments.pdf');
    Route::patch('/clients/{client}/payments/{payment}', [ClientController::class, 'updatePayment'])->name('clients.payments.update');
    Route::delete('/clients/{client}/payments/{payment}', [ClientController::class, 'destroyPayment'])->name('clients.payments.destroy');

    Route::resource('banks', BankController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('cheque-party-clients', ChequePartyClientController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['cheque-party-clients' => 'chequePartyClient']);
    Route::resource('cheque-party-fournisseurs', ChequePartyFournisseurController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['cheque-party-fournisseurs' => 'chequePartyFournisseur']);
    Route::resource('cheque-clients', ChequeClientController::class)->parameters(['cheque-clients' => 'chequeClient']);
    Route::get('/cheque-clients/{chequeClient}/pdf', [ChequeClientController::class, 'pdf'])->name('cheque-clients.pdf');
    Route::patch('/cheque-clients/{chequeClient}/status', [ChequeClientController::class, 'updateStatus'])->name('cheque-clients.status');
    Route::resource('cheque-fournisseurs', ChequeFournisseurController::class)->parameters(['cheque-fournisseurs' => 'chequeFournisseur']);
    Route::get('/cheque-fournisseurs/{chequeFournisseur}/pdf', [ChequeFournisseurController::class, 'pdf'])->name('cheque-fournisseurs.pdf');
    Route::patch('/cheque-fournisseurs/{chequeFournisseur}/status', [ChequeFournisseurController::class, 'updateStatus'])->name('cheque-fournisseurs.status');

    Route::resource('cheques', ChequeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::get('/cheques/{cheque}/pdf', [ChequeController::class, 'pdf'])->name('cheques.pdf');
    Route::patch('/cheques/{cheque}/status', [ChequeController::class, 'updateStatus'])->name('cheques.status');
});
