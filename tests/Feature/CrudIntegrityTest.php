<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\ChequeClient;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
