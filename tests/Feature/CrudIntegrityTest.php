<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\ChequeClient;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\User;
use App\Services\FournisseurService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CrudIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_supplier_statement_uses_one_cheque_record_as_payment(): void
    {
        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur']);
        $releve = $fournisseur->releveComptes()->create(['code_client' => 'REL-1', 'date_releve' => '2026-08-16']);

        $this->post(route('fournisseurs.releves.payments.store', [$fournisseur, $releve]), ['type' => 'cheque', 'numero_cheque' => 'F-001', 'banque' => 'Banque', 'montant' => 500, 'statut' => 'en_cours', 'facture_recue' => true, 'facture_donnee' => false])->assertSessionHas('success');

        $this->assertDatabaseHas('fournisseur_cheques', ['fournisseur_id' => $fournisseur->id, 'fournisseur_releve_compte_id' => $releve->id, 'numero_cheque' => 'F-001', 'facture_recue' => 1]);
    }

    public function test_client_payment_screen_accepts_cash_and_real_client_cheques(): void
    {
        $client = Client::create(['nom' => 'Client']);
        $this->post(route('clients.payments.store', $client), ['date_paiement' => '2026-08-16', 'montant' => 100, 'mode' => 'espece'])->assertSessionHas('success');
        $this->post(route('clients.cheques.store', $client), ['type' => 'effet', 'numero_cheque' => 'C-001', 'banque' => 'Banque', 'montant' => 200, 'statut' => 'en_cours'])->assertSessionHas('success');

        $this->assertDatabaseHas('client_payments', ['client_id' => $client->id, 'mode' => 'espece']);
        $this->assertDatabaseHas('cheque_clients', ['client_id' => $client->id, 'numero_cheque' => 'C-001']);
    }

    public function test_integrated_cheque_status_and_invoice_actions_work(): void
    {
        $client = Client::create(['nom' => 'Client']);
        $cheque = $client->cheques()->create(['type' => 'cheque', 'numero_cheque' => 'C-2', 'banque' => 'Banque', 'montant' => 50, 'statut' => 'en_cours']);

        $this->patch(route('clients.cheques.status', [$client, $cheque]), ['statut' => 'en_caisse', 'facture_recue' => true, 'facture_donnee' => true])->assertSessionHas('success');
        $this->assertDatabaseHas('cheque_clients', ['id' => $cheque->id, 'statut' => 'en_caisse', 'facture_recue' => 1, 'facture_donnee' => 1]);
    }

    public function test_used_bank_cannot_be_deleted_and_legacy_routes_are_gone(): void
    {
        $bank = Bank::create(['name' => 'Banque']);
        $client = Client::create(['nom' => 'Client']);
        ChequeClient::create(['client_id' => $client->id, 'bank_id' => $bank->id, 'type' => 'cheque', 'numero_cheque' => 'C-3', 'banque' => $bank->name, 'montant' => 50, 'statut' => 'en_cours']);

        $this->delete(route('settings.banks.destroy', $bank))->assertSessionHas('error');
        $this->get('/cheque-fournisseurs')->assertNotFound();
    }

    public function test_supplier_and_statement_financial_summaries_include_counts_and_totals(): void
    {
        Carbon::setTestNow('2026-09-01 10:00:00');
        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur résumé']);
        $firstReleve = $fournisseur->releveComptes()->create(['code_client' => 'REL-1', 'date_releve' => '2026-09-01']);
        $secondReleve = $fournisseur->releveComptes()->create(['code_client' => 'REL-2', 'date_releve' => '2026-09-02']);

        $firstReleve->factures()->create(['fournisseur_id' => $fournisseur->id, 'numero_facture' => 'FAC-1', 'date_facture' => '2026-09-01', 'montant' => 300]);
        $firstReleve->factures()->create(['fournisseur_id' => $fournisseur->id, 'numero_facture' => 'FAC-2', 'date_facture' => '2026-09-01', 'montant' => 200]);
        $firstReleve->cheques()->create(['fournisseur_id' => $fournisseur->id, 'type' => 'cheque', 'numero_cheque' => 'PAY-1', 'banque' => 'Banque', 'date_emission' => '2026-09-01', 'montant' => 150, 'statut' => 'en_cours']);
        $secondReleve->factures()->create(['fournisseur_id' => $fournisseur->id, 'numero_facture' => 'FAC-3', 'date_facture' => '2026-09-02', 'montant' => 100]);
        $secondReleve->cheques()->create(['fournisseur_id' => $fournisseur->id, 'type' => 'cheque', 'numero_cheque' => 'PAY-2', 'banque' => 'Banque', 'date_emission' => '2026-09-02', 'montant' => 50, 'statut' => 'en_cours']);

        $service = app(FournisseurService::class);
        $supplierSummary = $service->show($fournisseur, [])['fournisseur'];
        $statementSummary = $service->releve($fournisseur, $firstReleve, [])['releve'];

        $this->assertSame(3, $supplierSummary['factures_count']);
        $this->assertSame(2, $supplierSummary['payments_count']);
        $this->assertSame(600.0, $supplierSummary['total_factures']);
        $this->assertSame(200.0, $supplierSummary['total_paye']);
        $this->assertSame(400.0, $supplierSummary['balance']);
        $this->assertSame(2, $statementSummary['factures_count']);
        $this->assertSame(1, $statementSummary['payments_count']);
        $this->assertSame(500.0, $statementSummary['total_factures']);
        $this->assertSame(150.0, $statementSummary['total_paye']);
        $this->assertSame(350.0, $statementSummary['balance']);
        $this->assertSame(2, $supplierSummary['today_factures_count']);
        $this->assertSame(1, $supplierSummary['today_payments_count']);
        $this->assertSame(500.0, $supplierSummary['today_total_factures']);
        $this->assertSame(150.0, $supplierSummary['today_total_paye']);
        $this->assertSame(2, $statementSummary['today_factures_count']);
        $this->assertSame(1, $statementSummary['today_payments_count']);

        Carbon::setTestNow();
    }
}
