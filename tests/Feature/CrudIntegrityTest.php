<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Bank;
use App\Models\Cheque;
use App\Models\ChequeClient;
use App\Models\ChequePartyClient;
use App\Models\Client;
use App\Models\Depot;
use App\Models\Employee;
use App\Models\Fournisseur;
use App\Models\FournisseurReleveCompte;
use App\Models\Operation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
