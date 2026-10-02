<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleGroupController;
use App\Http\Controllers\BonLivraisonController;
use App\Http\Controllers\DevisController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaisseController;
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

    Route::middleware('permission:depots,admin_delete')->group(function (): void {
        Route::resource('depots', DepotController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::delete('/depots', [DepotController::class, 'destroySelected'])->name('depots.destroy-selected')->middleware('permission:admin');
        Route::resource('articles', ArticleController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::delete('/articles', [ArticleController::class, 'destroySelected'])->name('articles.destroy-selected')->middleware('permission:admin');
        Route::post('/depots/{depot}/adjust-stock', [DepotController::class, 'adjustStock'])->name('depots.adjust-stock');
        Route::resource('operations', OperationController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::delete('/operations', [OperationController::class, 'destroySelected'])->name('operations.destroy-selected')->middleware('permission:admin');
        Route::get('/operations/{operation}', [OperationController::class, 'show'])->name('operations.show');
        Route::get('/operations/{operation}/pdf', [OperationController::class, 'pdf'])->name('operations.pdf');

        Route::get('/groupes', [ArticleGroupController::class, 'index'])->name('groupes.index');
        Route::post('/groupes', [ArticleGroupController::class, 'store'])->name('groupes.store');
        Route::get('/groupes/{groupe}', [ArticleGroupController::class, 'show'])->name('groupes.show');
        Route::patch('/groupes/assign', [ArticleGroupController::class, 'assign'])->name('groupes.assign');
        Route::patch('/groupes/{groupe}/retirer', [ArticleGroupController::class, 'remove'])->name('groupes.remove');
        Route::patch('/groupes/{groupe}', [ArticleGroupController::class, 'update'])->name('groupes.update');
        Route::delete('/groupes/{groupe}', [ArticleGroupController::class, 'destroy'])->name('groupes.destroy');

        Route::get('/devis', [DevisController::class, 'index'])->name('devis.index');
        Route::get('/devis/create', [DevisController::class, 'create'])->name('devis.create');
        Route::post('/devis', [DevisController::class, 'store'])->name('devis.store');
        Route::get('/devis/{devis}/pdf', [DevisController::class, 'pdf'])->name('devis.pdf');
        Route::post('/devis/{devis}/lines', [DevisController::class, 'addLine'])->name('devis.lines.store');
        Route::patch('/devis/{devis}/lines/{line}', [DevisController::class, 'updateLine'])->name('devis.lines.update');
        Route::patch('/devis/{devis}/lines/{line}/retirer', [DevisController::class, 'removeLine'])->name('devis.lines.remove');
        Route::patch('/devis/{devis}/lines/{line}/validate', [DevisController::class, 'validateLine'])->name('devis.lines.validate');
        Route::patch('/devis/{devis}/cancel', [DevisController::class, 'cancel'])->name('devis.cancel');
        Route::delete('/devis/{devis}', [DevisController::class, 'destroy'])->name('devis.destroy');

        Route::get('/livraisons', [BonLivraisonController::class, 'index'])->name('livraisons.index');
        Route::get('/livraisons/create', [BonLivraisonController::class, 'create'])->name('livraisons.create');
        Route::post('/livraisons', [BonLivraisonController::class, 'store'])->name('livraisons.store');
        Route::get('/livraisons/{livraison}/pdf', [BonLivraisonController::class, 'pdf'])->name('livraisons.pdf');
        Route::delete('/livraisons/{livraison}', [BonLivraisonController::class, 'destroy'])->name('livraisons.destroy');
    });
    Route::middleware('permission:cheques')->group(function (): void {
        Route::get('/cheques/impayes', [ChequeImpayeController::class, 'index'])->name('cheques.impayes.index');
        Route::post('/cheques/impayes', [ChequeImpayeController::class, 'store'])->name('cheques.impayes.store');
        Route::patch('/cheques/impayes/{chequeImpaye}/payer', [ChequeImpayeController::class, 'pay'])->name('cheques.impayes.pay');
        Route::patch('/cheques/impayes/{chequeImpaye}', [ChequeImpayeController::class, 'update'])->name('cheques.impayes.update');
        Route::delete('/cheques/impayes/{chequeImpaye}', [ChequeImpayeController::class, 'destroy'])->name('cheques.impayes.destroy');
        Route::delete('/cheques/impayes', [ChequeImpayeController::class, 'destroySelected'])->name('cheques.impayes.destroy-selected')->middleware('permission:admin');
        Route::get('/cheques', [ChequeController::class, 'index'])->name('cheques.index');
        Route::get('/cheques/export', [ChequeController::class, 'export'])->name('cheques.export');
        Route::post('/cheques', [ChequeController::class, 'store'])->name('cheques.store');
        Route::patch('/cheques/{cheque}/sortie', [ChequeController::class, 'updateSortie'])->name('cheques.sortie');
        Route::patch('/cheques/{cheque}/inline', [ChequeController::class, 'updateInline'])->name('cheques.inline');
        Route::patch('/cheques/{cheque}', [ChequeController::class, 'update'])->name('cheques.update');
        Route::delete('/cheques/{cheque}', [ChequeController::class, 'destroy'])->name('cheques.destroy');
        Route::delete('/cheques', [ChequeController::class, 'destroySelected'])->name('cheques.destroy-selected')->middleware('permission:admin');
    });
    Route::middleware('permission:admin')->group(function (): void {
        Route::resource('employees', EmployeeController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::delete('/employees', [EmployeeController::class, 'destroySelected'])->name('employees.destroy-selected')->middleware('permission:admin');
        Route::get('/employees/{employee}/payments', [EmployeeController::class, 'paymentHistory'])->name('employees.payments.index');
        Route::get('/employees/{employee}/absences', [EmployeeController::class, 'absenceHistory'])->name('employees.absences.index');
        Route::post('/employees/{employee}/work-days', [EmployeeController::class, 'storeWorkDay'])->name('employees.work-days.store');
        Route::post('/employees/{employee}/absences', [EmployeeController::class, 'storeAbsence'])->name('employees.absences.store');
        Route::post('/employees/{employee}/salary-payments', [EmployeeController::class, 'storeSalaryPayment'])->name('employees.salary-payments.store');
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
        Route::delete('/fournisseurs/releves', [FournisseurController::class, 'destroySelectedRelevesGlobal'])->name('fournisseurs.releves.index.destroy-selected')->middleware('permission:admin');
        Route::resource('fournisseurs', FournisseurController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
        Route::delete('/fournisseurs', [FournisseurController::class, 'destroySelected'])->name('fournisseurs.destroy-selected')->middleware('permission:admin');
        Route::post('/fournisseurs/{fournisseur}/releves', [FournisseurController::class, 'storeReleve'])->name('fournisseurs.releves.store');
        Route::delete('/fournisseurs/{fournisseur}/releves', [FournisseurController::class, 'destroySelectedReleves'])->name('fournisseurs.releves.destroy-selected')->middleware('permission:admin');
        Route::get('/fournisseurs/{fournisseur}/releves/{releve}', [FournisseurController::class, 'showReleve'])->name('fournisseurs.releves.show');
        Route::patch('/fournisseurs/{fournisseur}/releves/{releve}', [FournisseurController::class, 'updateReleve'])->name('fournisseurs.releves.update');
        Route::delete('/fournisseurs/{fournisseur}/releves/{releve}', [FournisseurController::class, 'destroyReleve'])->name('fournisseurs.releves.destroy');
        Route::get('/fournisseurs/{fournisseur}/releves/{releve}/pdf', [FournisseurController::class, 'pdfReleve'])->name('fournisseurs.releves.pdf');
        Route::post('/fournisseurs/{fournisseur}/releves/{releve}/factures', [FournisseurController::class, 'storeReleveFacture'])->name('fournisseurs.releves.factures.store');
        Route::patch('/fournisseurs/{fournisseur}/releves/{releve}/factures/{facture}', [FournisseurController::class, 'updateFacture'])->name('fournisseurs.releves.factures.update');
        Route::delete('/fournisseurs/{fournisseur}/releves/{releve}/factures/{facture}', [FournisseurController::class, 'destroyFacture'])->name('fournisseurs.releves.factures.destroy');
        Route::delete('/fournisseurs/{fournisseur}/releves/{releve}/factures', [FournisseurController::class, 'destroySelectedFactures'])->name('fournisseurs.releves.factures.destroy-selected')->middleware('permission:admin');
        Route::post('/fournisseurs/{fournisseur}/releves/{releve}/payments', [FournisseurController::class, 'storeRelevePayment'])->name('fournisseurs.releves.payments.store');
        Route::get('/fournisseurs/{fournisseur}/releves/{releve}/payments/{payment}/pdf', [FournisseurController::class, 'pdfPayment'])->name('fournisseurs.releves.payments.pdf');
        Route::patch('/fournisseurs/{fournisseur}/releves/{releve}/payments/{payment}', [FournisseurController::class, 'updatePayment'])->name('fournisseurs.releves.payments.update');
        Route::delete('/fournisseurs/{fournisseur}/releves/{releve}/payments/{payment}', [FournisseurController::class, 'destroyPayment'])->name('fournisseurs.releves.payments.destroy');
        Route::delete('/fournisseurs/{fournisseur}/releves/{releve}/payments', [FournisseurController::class, 'destroySelectedPayments'])->name('fournisseurs.releves.payments.destroy-selected')->middleware('permission:admin');
        Route::post('/fournisseurs/{fournisseur}/factures', [FournisseurController::class, 'storeFacture'])->name('fournisseurs.factures.store');
        Route::patch('/fournisseurs/{fournisseur}/releves/{releve}/cheques/{cheque}/status', [FournisseurController::class, 'updateChequeStatus'])->name('fournisseurs.releves.cheques.status');
    });

    Route::middleware('permission:caisse')->group(function (): void {
        Route::resource('caisse', CaisseController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::delete('/caisse', [CaisseController::class, 'destroySelected'])->name('caisse.destroy-selected')->middleware('permission:admin');
        Route::post('/caisse/quick-client', [CaisseController::class, 'quickStoreClient'])->name('caisse.quick-client');
        Route::post('/caisse/quick-fournisseur', [CaisseController::class, 'quickStoreFournisseur'])->name('caisse.quick-fournisseur');
        Route::patch('/caisse/{caisse}/validate', [CaisseController::class, 'approve'])->name('caisse.validate')->middleware('permission:admin');
    });

    Route::middleware('permission:clients')->group(function (): void {
        Route::resource('clients', ClientController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
        Route::get('/clients/{client}/releve/pdf', [ClientController::class, 'pdfReleve'])->name('clients.releve.pdf');
        Route::post('/clients/{client}/entries', [ClientController::class, 'storeEntry'])->name('clients.entries.store');
        Route::patch('/clients/{client}/entries/{entry}', [ClientController::class, 'updateEntry'])->name('clients.entries.update');
        Route::delete('/clients/{client}/entries/{entry}', [ClientController::class, 'destroyEntry'])->name('clients.entries.destroy');
        Route::delete('/clients/{client}/entries', [ClientController::class, 'destroySelectedEntries'])->name('clients.entries.destroy-selected')->middleware('permission:admin');
        Route::post('/clients/{client}/payments', [ClientController::class, 'storePayment'])->name('clients.payments.store');
        Route::get('/clients/{client}/payments/{payment}/pdf', [ClientController::class, 'pdfPayment'])->name('clients.payments.pdf');
        Route::patch('/clients/{client}/payments/{payment}', [ClientController::class, 'updatePayment'])->name('clients.payments.update');
        Route::delete('/clients/{client}/payments/{payment}', [ClientController::class, 'destroyPayment'])->name('clients.payments.destroy');
        Route::delete('/clients/{client}/payments', [ClientController::class, 'destroySelectedPayments'])->name('clients.payments.destroy-selected')->middleware('permission:admin');
        Route::post('/clients/{client}/cheques', [ClientController::class, 'storeCheque'])->name('clients.cheques.store');
        Route::patch('/clients/{client}/cheques/{cheque}', [ClientController::class, 'updateCheque'])->name('clients.cheques.update');
        Route::patch('/clients/{client}/cheques/{cheque}/status', [ClientController::class, 'updateChequeStatus'])->name('clients.cheques.status');
        Route::delete('/clients/{client}/cheques/{cheque}', [ClientController::class, 'destroyCheque'])->name('clients.cheques.destroy');
    });

});
