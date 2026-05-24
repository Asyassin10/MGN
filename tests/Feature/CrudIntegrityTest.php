<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Bank;
use App\Models\Cheque;
use App\Models\ChequeClient;
use App\Models\ChequeFournisseur;
use App\Models\ChequePartyClient;
use App\Models\ChequePartyFournisseur;
use App\Models\Client;
use App\Models\Depot;
use App\Models\Employee;
use App\Models\Fournisseur;
use App\Models\FournisseurCheque;
use App\Models\FournisseurReleveCompte;
use App\Models\Operation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CrudIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_depot_and_article_with_stock_cannot_be_deleted(): void
    {
        $depot = Depot::create(['name' => 'Central']);
        $article = Article::create(['reference' => 'A-1', 'name' => 'Article']);
        $depot->articles()->attach($article, ['quantity' => 4]);

        $this->delete(route('depots.destroy', $depot))
            ->assertSessionHas('error');
        $this->delete(route('articles.destroy', $article))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('depots', ['id' => $depot->id]);
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    public function test_master_records_with_history_cannot_be_deleted(): void
    {
        $employee = Employee::create(['name' => 'Samir']);
        $depot = Depot::create(['name' => 'Central']);
        Operation::create(['reference' => 'OP-HISTORY', 'type' => 'entree', 'depot_id' => $depot->id, 'employee_id' => $employee->id]);

        $client = Client::create(['nom' => 'Client']);
        $client->entries()->create(['date_entree' => '2026-05-01', 'montant' => 50, 'description' => 'Vente']);

        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur']);
        $fournisseur->releveComptes()->create(['code_client' => 'REL-1', 'date_releve' => '2026-05-01']);

        $party = ChequePartyClient::create(['nom' => 'Tiers']);
        $bank = Bank::create(['name' => 'Banque']);
        ChequeClient::create([
            'client_id' => $party->id,
            'bank_id' => $bank->id,
            'numero_cheque' => 'CH-1',
            'banque' => 'Banque',
            'montant' => 100,
        ]);
        $standaloneBank = Bank::create(['name' => 'Banque autonome']);
        Cheque::create([
            'type' => 'client',
            'numero_cheque' => 'CH-2',
            'banque' => $standaloneBank->name,
            'montant' => 200,
        ]);

        $this->delete(route('employees.destroy', $employee))->assertSessionHas('error');
        $this->delete(route('clients.destroy', $client))->assertSessionHas('error');
        $this->delete(route('fournisseurs.destroy', $fournisseur))->assertSessionHas('error');
        $this->delete(route('banks.destroy', $bank))->assertSessionHas('error');
        $this->delete(route('banks.destroy', $standaloneBank))->assertSessionHas('error');
        $this->delete(route('cheque-party-clients.destroy', $party))->assertSessionHas('error');
    }

    public function test_empty_client_supplier_and_statement_can_be_deleted(): void
    {
        $client = Client::create(['nom' => 'Client vide']);
        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur vide']);
        $releve = $fournisseur->releveComptes()->create(['code_client' => 'REL-VIDE', 'date_releve' => '2026-05-01']);

        $this->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success');
        $this->delete(route('fournisseurs.releves.destroy', [$fournisseur, $releve]))
            ->assertRedirect(route('fournisseurs.show', $fournisseur))
            ->assertSessionHas('success');
        $this->delete(route('fournisseurs.destroy', $fournisseur))
            ->assertRedirect(route('fournisseurs.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
        $this->assertSoftDeleted('fournisseurs', ['id' => $fournisseur->id]);
        $this->assertDatabaseMissing('fournisseur_releve_comptes', ['id' => $releve->id]);
    }

    public function test_delete_errors_name_the_exact_blocking_history(): void
    {
        $client = Client::create(['nom' => 'Client avec historique']);
        $client->entries()->create(['date_entree' => '2026-05-01', 'montant' => 50, 'description' => 'Vente']);
        $client->payments()->create(['date_paiement' => '2026-05-02', 'montant' => 20, 'mode' => 'espece']);

        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur avec historique']);
        $releve = $fournisseur->releveComptes()->create(['code_client' => 'REL-BLOCK', 'date_releve' => '2026-05-01']);
        $releve->factures()->create([
            'fournisseur_id' => $fournisseur->id,
            'numero_facture' => 'FAC-BLOCK',
            'date_facture' => '2026-05-01',
            'montant' => 100,
        ]);

        $this->delete(route('clients.destroy', $client))
            ->assertSessionHas('error', 'Impossible de supprimer ce client : supprimez d’abord 1 entrées et 1 paiements.');
        $this->delete(route('fournisseurs.releves.destroy', [$fournisseur, $releve]))
            ->assertSessionHas('error', 'Impossible de supprimer ce relevé : supprimez d’abord 1 factures.');
        $this->delete(route('fournisseurs.destroy', $fournisseur))
            ->assertSessionHas('error', 'Impossible de supprimer ce fournisseur : supprimez d’abord 1 relevés compte et 1 factures.');
    }

    public function test_delete_errors_are_specific_for_stock_cheque_bank_and_employee_records(): void
    {
        $employee = Employee::create(['name' => 'Employé historique']);
        $depot = Depot::create(['name' => 'Dépôt historique']);
        $article = Article::create(['reference' => 'ART-BLOCK', 'name' => 'Article historique']);
        $depot->articles()->attach($article, ['quantity' => 9]);
        $operation = Operation::create([
            'reference' => 'OP-BLOCKERS',
            'type' => 'entree',
            'depot_id' => $depot->id,
            'employee_id' => $employee->id,
        ]);
        $operation->lines()->create(['article_id' => $article->id, 'reference' => $article->reference, 'quantity' => 2]);

        $bank = Bank::create(['name' => 'Banque historique']);
        $clientParty = ChequePartyClient::create(['nom' => 'Client chèque historique']);
        ChequeClient::create([
            'client_id' => $clientParty->id,
            'bank_id' => $bank->id,
            'numero_cheque' => 'CHC-BLOCK',
            'banque' => $bank->name,
            'montant' => 100,
        ]);

        $fournisseurParty = ChequePartyFournisseur::create(['nom' => 'Fournisseur chèque historique']);
        ChequeFournisseur::create([
            'fournisseur_id' => $fournisseurParty->id,
            'bank_id' => $bank->id,
            'numero_cheque' => 'CHF-BLOCK',
            'banque' => $bank->name,
            'montant' => 200,
        ]);
        Cheque::create([
            'type' => 'client',
            'numero_cheque' => 'CH-BLOCK',
            'banque' => $bank->name,
            'montant' => 300,
        ]);

        $this->delete(route('employees.destroy', $employee))
            ->assertSessionHas('error', 'Impossible de supprimer cet employé : supprimez d’abord 1 opérations.');
        $this->delete(route('depots.destroy', $depot))
            ->assertSessionHas('error', 'Impossible de supprimer ce dépôt : supprimez d’abord 1 opérations et 1 articles avec stock non nul.');
        $this->delete(route('articles.destroy', $article))
            ->assertSessionHas('error', 'Impossible de supprimer cet article : supprimez d’abord 1 lignes d’opérations et 1 dépôts avec stock non nul.');
        $this->delete(route('cheque-party-clients.destroy', $clientParty))
            ->assertSessionHas('error', 'Impossible de supprimer ce client chèque : supprimez d’abord 1 chèques clients.');
        $this->delete(route('cheque-party-fournisseurs.destroy', $fournisseurParty))
            ->assertSessionHas('error', 'Impossible de supprimer ce fournisseur chèque : supprimez d’abord 1 chèques fournisseurs.');
        $this->delete(route('banks.destroy', $bank))
            ->assertSessionHas('error', 'Impossible de supprimer cette banque : supprimez d’abord 1 chèques clients, 1 chèques fournisseurs et 1 chèques.');
    }

    public function test_due_cheques_command_moves_only_due_en_cours_cheques_to_in_caisse(): void
    {
        Carbon::setTestNow('2026-05-24 10:00:00');

        $bank = Bank::create(['name' => 'Banque échéance']);
        $clientParty = ChequePartyClient::create(['nom' => 'Client échéance']);
        $fournisseurParty = ChequePartyFournisseur::create(['nom' => 'Fournisseur échéance']);
        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur interne']);

        $standaloneDue = Cheque::create([
            'type' => 'client',
            'numero_cheque' => 'STD-DUE',
            'banque' => $bank->name,
            'montant' => 100,
            'date_echeance' => '2026-05-24',
            'statut' => 'en_cours',
        ]);
        $standaloneFuture = Cheque::create([
            'type' => 'client',
            'numero_cheque' => 'STD-FUTURE',
            'banque' => $bank->name,
            'montant' => 100,
            'date_echeance' => '2026-05-25',
            'statut' => 'en_cours',
        ]);
        $clientDue = ChequeClient::create([
            'client_id' => $clientParty->id,
            'bank_id' => $bank->id,
            'numero_cheque' => 'CLC-DUE',
            'banque' => $bank->name,
            'montant' => 100,
            'date_echeance' => '2026-05-24',
            'statut' => 'en_cours',
        ]);
        $clientImpaye = ChequeClient::create([
            'client_id' => $clientParty->id,
            'bank_id' => $bank->id,
            'numero_cheque' => 'CLC-IMPAYE',
            'banque' => $bank->name,
            'montant' => 100,
            'date_echeance' => '2026-05-24',
            'statut' => 'impaye',
        ]);
        $fournisseurDue = ChequeFournisseur::create([
            'fournisseur_id' => $fournisseurParty->id,
            'bank_id' => $bank->id,
            'numero_cheque' => 'CHF-DUE',
            'banque' => $bank->name,
            'montant' => 100,
            'date_echeance' => '2026-05-24',
            'statut' => 'en_cours',
        ]);
        $internalFournisseurDue = FournisseurCheque::create([
            'fournisseur_id' => $fournisseur->id,
            'numero_cheque' => 'FIN-DUE',
            'banque' => $bank->name,
            'montant' => 100,
            'date_echeance' => '2026-05-24',
            'statut' => 'en_cours',
        ]);

        $this->artisan('cheques:mark-due-in-caisse')
            ->expectsOutput('4 chèque(s) mis en caisse.')
            ->assertExitCode(0);

        $this->assertSame('encaisse', $standaloneDue->fresh()->statut);
        $this->assertSame('en_cours', $standaloneFuture->fresh()->statut);
        $this->assertSame('en_caisse', $clientDue->fresh()->statut);
        $this->assertSame('impaye', $clientImpaye->fresh()->statut);
        $this->assertSame('en_caisse', $fournisseurDue->fresh()->statut);
        $this->assertSame('en_caisse', $internalFournisseurDue->fresh()->statut);

        Carbon::setTestNow();
    }

    public function test_empty_supplier_statement_can_be_updated_and_deleted(): void
    {
        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur']);
        $releve = $fournisseur->releveComptes()->create(['code_client' => 'REL-1', 'date_releve' => '2026-05-01']);

        $this->patch(route('fournisseurs.releves.update', [$fournisseur, $releve]), [
            'code_client' => 'REL-2',
            'date_releve' => '2026-05-02',
            'note' => 'Corrigé',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('fournisseur_releve_comptes', ['id' => $releve->id, 'code_client' => 'REL-2']);

        $this->delete(route('fournisseurs.releves.destroy', [$fournisseur, $releve]))
            ->assertRedirect(route('fournisseurs.show', $fournisseur));

        $this->assertDatabaseMissing('fournisseur_releve_comptes', ['id' => $releve->id]);
    }

    public function test_supplier_creation_and_statement_creation_return_to_their_lists(): void
    {
        $this->post(route('fournisseurs.store'), [
            'nom' => 'Fournisseur simple',
            'telephone' => '',
            'ville' => '',
            'note' => '',
        ])->assertRedirect(route('fournisseurs.index'));

        $fournisseur = Fournisseur::query()->where('nom', 'Fournisseur simple')->firstOrFail();

        $this->post(route('fournisseurs.releves.store', $fournisseur), [
            'code_client' => 'REL-SIMPLE',
            'date_releve' => '2026-05-23',
            'note' => '',
        ])->assertRedirect(route('fournisseurs.show', $fournisseur));

        $this->assertDatabaseHas('fournisseur_releve_comptes', [
            'fournisseur_id' => $fournisseur->id,
            'code_client' => 'REL-SIMPLE',
        ]);
    }

    public function test_supplier_statement_with_financial_lines_cannot_be_deleted(): void
    {
        $fournisseur = Fournisseur::create(['nom' => 'Fournisseur']);
        $releve = $fournisseur->releveComptes()->create(['code_client' => 'REL-1', 'date_releve' => '2026-05-01']);
        $releve->factures()->create([
            'fournisseur_id' => $fournisseur->id,
            'numero_facture' => 'FAC-1',
            'date_facture' => '2026-05-01',
            'montant' => 125,
        ]);

        $this->delete(route('fournisseurs.releves.destroy', [$fournisseur, $releve]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('fournisseur_releve_comptes', ['id' => $releve->id]);
    }

    public function test_global_supplier_statement_list_filters_rows_and_returns_after_delete(): void
    {
        $alpha = Fournisseur::create(['nom' => 'Alpha Droguerie']);
        $alphaReleve = $alpha->releveComptes()->create(['code_client' => 'REL-ALPHA', 'date_releve' => '2026-05-15']);
        $beta = Fournisseur::create(['nom' => 'Beta Droguerie']);
        $beta->releveComptes()->create(['code_client' => 'REL-BETA', 'date_releve' => '2026-06-15']);

        $this->get(route('fournisseurs.releves.index', [
            'search' => 'Alpha',
            'fournisseur_id' => $alpha->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('Fournisseurs/RelevesIndex')
            ->has('releves.data', 1)
            ->where('releves.data.0.fournisseur_nom', 'Alpha Droguerie')
            ->where('releves.data.0.code_client', 'REL-ALPHA'));

        $this->delete(route('fournisseurs.releves.destroy', [
            'fournisseur' => $alpha,
            'releve' => $alphaReleve,
            'return' => 'index',
        ]))->assertRedirect(route('fournisseurs.releves.index'));
    }

    public function test_database_seeder_imports_the_complete_bundled_article_catalog(): void
    {
        $depot = Depot::create(['name' => 'Dépôt Central', 'location' => 'Casablanca']);
        $article = Article::create(['reference' => '00001', 'name' => 'Article existant']);
        $depot->articles()->attach($article, ['quantity' => 27]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('articles', 312);
        $this->assertDatabaseHas('articles', [
            'reference' => '00312',
            'name' => 'لندوي بلون د بلون اطلس 25كلغ',
        ]);
        $this->assertDatabaseCount('depot_article', 936);
        $this->assertSame(27, $this->quantity($depot, $article));
    }

    public function test_operation_update_reverses_old_stock_before_applying_new_stock(): void
    {
        [$depot, $article] = $this->stockFixture(10);

        $this->post(route('operations.store'), [
            'type' => 'entree',
            'depot_id' => $depot->id,
            'employee_id' => null,
            'note' => '',
            'lines' => [['article_id' => $article->id, 'quantity' => 5]],
        ])->assertRedirect();

        $operation = Operation::query()->latest('id')->firstOrFail();

        $this->patch(route('operations.update', $operation), [
            'type' => 'sortie',
            'depot_id' => $depot->id,
            'employee_id' => null,
            'note' => '',
            'lines' => [['article_id' => $article->id, 'quantity' => 3]],
        ])->assertRedirect(route('operations.show', $operation));

        $this->assertSame(7, $this->quantity($depot, $article));
    }

    public function test_operation_delete_reverses_stock_and_rejects_consumed_entry(): void
    {
        [$depot, $article] = $this->stockFixture(10);
        $this->post(route('operations.store'), [
            'type' => 'entree',
            'depot_id' => $depot->id,
            'employee_id' => null,
            'note' => '',
            'lines' => [['article_id' => $article->id, 'quantity' => 5]],
        ]);
        $operation = Operation::query()->latest('id')->firstOrFail();

        $this->delete(route('operations.destroy', $operation))
            ->assertRedirect(route('operations.index'));
        $this->assertSame(10, $this->quantity($depot, $article));

        $this->post(route('operations.store'), [
            'type' => 'entree',
            'depot_id' => $depot->id,
            'employee_id' => null,
            'note' => '',
            'lines' => [['article_id' => $article->id, 'quantity' => 5]],
        ]);
        $blocked = Operation::query()->latest('id')->firstOrFail();
        $depot->articles()->updateExistingPivot($article->id, ['quantity' => 3]);

        $this->delete(route('operations.destroy', $blocked))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('operations', ['id' => $blocked->id]);
        $this->assertSame(3, $this->quantity($depot, $article));
    }

    public function test_generated_pdf_is_streamed_inline_without_saving_a_public_file(): void
    {
        Storage::fake('public');
        [$depot] = $this->stockFixture(10);
        $operation = Operation::create([
            'reference' => 'OP-PDF',
            'type' => 'entree',
            'depot_id' => $depot->id,
            'employee_id' => null,
            'note' => null,
        ]);

        $response = $this->get(route('operations.pdf', $operation));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('inline;', (string) $response->headers->get('content-disposition'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function stockFixture(int $quantity): array
    {
        $depot = Depot::create(['name' => 'Central']);
        $article = Article::create(['reference' => 'A-1', 'name' => 'Article']);
        $depot->articles()->attach($article, ['quantity' => $quantity]);

        return [$depot, $article];
    }

    private function quantity(Depot $depot, Article $article): int
    {
        return (int) $depot->articles()->where('article_id', $article->id)->firstOrFail()->pivot->quantity;
    }
}
