<?php

namespace Tests\Feature;

use App\Models\Cheque;
use App\Models\ChequeImpaye;
use App\Models\User;
use App\Services\ChequeMaturityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndependentChequeModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_an_independent_client_cheque_and_its_sortie(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('cheques.store'), $this->chequeData())->assertSessionHas('success');
        $cheque = Cheque::firstOrFail();

        $this->assertDatabaseHas('cheques', [
            'numero_cheque' => 'CH-100',
            'client_nom' => 'Client libre',
            'statut' => 'en_cours',
            'est_sorti' => 0,
        ]);

        $this->patch(route('cheques.inline', $cheque), [
            'statut' => 'impaye',
        ])->assertSessionHas('success');

        $this->patch(route('cheques.sortie', $cheque), ['est_sorti' => true])->assertSessionHasErrors('fournisseur_sortie_nom');
        $this->patch(route('cheques.sortie', $cheque), ['est_sorti' => true, 'fournisseur_sortie_nom' => 'Fournisseur libre'])->assertSessionHas('success');

        $this->assertDatabaseHas('cheques', [
            'id' => $cheque->id,
            'statut' => 'impaye',
            'est_sorti' => 1,
            'fournisseur_sortie_nom' => 'Fournisseur libre',
        ]);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Chèques', 'subject_type' => 'Cheque']);

        $this->patch(route('cheques.sortie', $cheque), ['est_sorti' => false, 'fournisseur_sortie_nom' => 'Ignored'])->assertSessionHas('success');
        $this->assertDatabaseHas('cheques', ['id' => $cheque->id, 'est_sorti' => 0, 'fournisseur_sortie_nom' => null]);
    }

    public function test_due_independent_cheques_are_automatically_marked_in_caisse(): void
    {
        $cheque = Cheque::create($this->chequeData(['date_echeance' => '2026-08-20']));

        app(ChequeMaturityService::class)->markDueChequesInCaisse(now()->setDate(2026, 8, 20));

        $this->assertSame('en_caisse', $cheque->fresh()->statut);
    }

    public function test_available_total_excludes_cheques_marked_as_sortis(): void
    {
        $this->actingAs(User::factory()->create());
        Cheque::create($this->chequeData(['numero_cheque' => 'CH-1', 'montant' => 10]));
        Cheque::create($this->chequeData(['numero_cheque' => 'CH-2', 'montant' => 10]));
        Cheque::create($this->chequeData(['numero_cheque' => 'CH-3', 'montant' => 10]));
        Cheque::create($this->chequeData(['numero_cheque' => 'CH-4', 'montant' => 10, 'est_sorti' => true, 'fournisseur_sortie_nom' => 'Fournisseur']));

        $this->assertSame(30.0, (float) Cheque::query()->where('est_sorti', false)->sum('montant'));
        $this->assertSame(3, Cheque::query()->where('est_sorti', false)->count());
    }

    public function test_cheques_can_be_filtered_by_supplier_and_exported_as_excel(): void
    {
        $this->actingAs(User::factory()->create());
        $selected = Cheque::create($this->chequeData([
            'numero_cheque' => 'CH-SUPPLIER',
            'est_sorti' => true,
            'fournisseur_sortie_nom' => 'Fournisseur Atlas',
        ]));
        Cheque::create($this->chequeData([
            'numero_cheque' => 'CH-OTHER',
            'est_sorti' => true,
            'fournisseur_sortie_nom' => 'Fournisseur Autre',
        ]));

        $this->get(route('cheques.index', ['fournisseur' => 'Atlas']))
            ->assertInertia(fn ($page) => $page
                ->component('Cheques/Index')
                ->has('cheques.data', 1)
                ->where('cheques.data.0.numero_cheque', 'CH-SUPPLIER'));

        $response = $this->get(route('cheques.export', [
            'fournisseur' => 'Atlas',
            'selected_ids' => [$selected->id],
        ]));

        $response->assertDownload('cheques-export.xls');
        $this->assertStringContainsString('CH-SUPPLIER', $response->streamedContent());
        $this->assertStringNotContainsString('CH-OTHER', $response->streamedContent());
    }

    public function test_impaye_starts_unpaid_and_pay_action_keeps_it_with_payment_date(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('cheques.impayes.store'), [
            'type' => 'cheque',
            'numero_cheque' => 'IMP-1',
            'fournisseur_nom' => 'Fournisseur libre',
            'client_nom' => 'Client responsable',
            'tireur_signataire' => 'Signataire',
            'date_remise' => '2026-08-19',
            'montant' => 900,
            'note' => 'Suivi manuel',
        ])->assertSessionHas('success');

        $impaye = ChequeImpaye::firstOrFail();
        $this->assertSame('impaye', $impaye->statut);
        $this->assertNull($impaye->date_paiement);
        $this->assertSame('Client responsable', $impaye->client_nom);

        $this->patch(route('cheques.impayes.pay', $impaye), [])->assertSessionHasErrors(['date_paiement', 'mode_paiement']);
        $this->patch(route('cheques.impayes.pay', $impaye), ['date_paiement' => '2026-08-20', 'mode_paiement' => 'virement'])->assertSessionHas('success');

        $impaye->refresh();
        $this->assertSame('paye', $impaye->statut);
        $this->assertSame('2026-08-20', $impaye->date_paiement?->format('Y-m-d'));
        $this->assertSame('virement', $impaye->mode_paiement);

        $this->patch(route('cheques.impayes.update', $impaye), [
            'type' => 'cheque', 'numero_cheque' => 'IMP-1', 'fournisseur_nom' => 'Fournisseur libre', 'client_nom' => 'Client responsable', 'tireur_signataire' => 'Signataire', 'date_remise' => '2026-08-19', 'montant' => 900, 'note' => 'Mis à jour',
            'statut' => 'paye', 'date_paiement' => '2026-08-21', 'mode_paiement' => 'cheque',
        ])->assertSessionHas('success');
        $impaye->refresh();
        $this->assertSame('cheque', $impaye->mode_paiement);
        $this->assertSame('2026-08-21', $impaye->date_paiement?->format('Y-m-d'));

        $this->patch(route('cheques.impayes.update', $impaye), [
            'type' => 'cheque', 'numero_cheque' => 'IMP-1', 'fournisseur_nom' => 'Fournisseur libre', 'client_nom' => 'Client responsable', 'tireur_signataire' => 'Signataire', 'date_remise' => '2026-08-19', 'montant' => 900, 'note' => 'Revenu impayé',
            'statut' => 'impaye', 'date_paiement' => '', 'mode_paiement' => '',
        ])->assertSessionHas('success');
        $impaye->refresh();
        $this->assertSame('impaye', $impaye->statut);
        $this->assertNull($impaye->date_paiement);
        $this->assertNull($impaye->mode_paiement);
    }

    public function test_restricted_user_needs_cheque_and_delete_permissions(): void
    {
        $withoutAccess = User::factory()->create(['role' => 'restricted', 'permissions' => ['modules' => [], 'delete' => []]]);
        $this->actingAs($withoutAccess)->get(route('cheques.index'))->assertForbidden();

        $withAccess = User::factory()->create(['role' => 'restricted', 'permissions' => ['modules' => ['cheques'], 'delete' => []]]);
        $cheque = Cheque::create($this->chequeData());
        $this->actingAs($withAccess)->get(route('cheques.index'))->assertOk();
        $this->actingAs($withAccess)->delete(route('cheques.destroy', $cheque))->assertForbidden();

        $withDelete = User::factory()->create(['role' => 'restricted', 'permissions' => ['modules' => ['cheques'], 'delete' => ['cheques']]]);
        $this->actingAs($withDelete)->delete(route('cheques.destroy', $cheque))->assertSessionHas('success');
    }

    private function chequeData(array $overrides = []): array
    {
        return [...[
            'type' => 'cheque',
            'numero_cheque' => 'CH-100',
            'client_nom' => 'Client libre',
            'tireur_signataire' => 'Signataire',
            'date_emission' => '2026-08-01',
            'date_echeance' => '2026-08-30',
            'statut' => 'en_cours',
            'montant' => 1200,
            'note' => 'Note',
        ], ...$overrides];
    }
}
