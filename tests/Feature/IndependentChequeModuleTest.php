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

    public function test_admin_can_manage_an_independent_client_cheque_and_inline_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('cheques.store'), $this->chequeData())->assertSessionHas('success');
        $cheque = Cheque::firstOrFail();

        $this->assertDatabaseHas('cheques', [
            'numero_cheque' => 'CH-100',
            'client_nom' => 'Client libre',
            'statut' => 'en_cours',
            'facture_recue' => 0,
        ]);

        $this->patch(route('cheques.inline', $cheque), [
            'statut' => 'impaye',
            'facture_recue' => true,
            'facture_donnee' => true,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('cheques', [
            'id' => $cheque->id,
            'statut' => 'impaye',
            'facture_recue' => 1,
            'facture_donnee' => 1,
        ]);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Chèques', 'subject_type' => 'Cheque']);
    }

    public function test_due_independent_cheques_are_automatically_marked_in_caisse(): void
    {
        $cheque = Cheque::create($this->chequeData(['date_echeance' => '2026-08-20']));

        app(ChequeMaturityService::class)->markDueChequesInCaisse(now()->setDate(2026, 8, 20));

        $this->assertSame('en_caisse', $cheque->fresh()->statut);
    }

    public function test_impaye_starts_unpaid_and_pay_action_keeps_it_with_payment_date(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('cheques.impayes.store'), [
            'type' => 'cheque',
            'numero_cheque' => 'IMP-1',
            'fournisseur_nom' => 'Fournisseur libre',
            'tireur_signataire' => 'Signataire',
            'date_remise' => '2026-08-19',
            'montant' => 900,
            'note' => 'Suivi manuel',
        ])->assertSessionHas('success');

        $impaye = ChequeImpaye::firstOrFail();
        $this->assertSame('impaye', $impaye->statut);
        $this->assertNull($impaye->date_paiement);

        $this->patch(route('cheques.impayes.pay', $impaye), [])->assertSessionHasErrors(['date_paiement', 'mode_paiement']);
        $this->patch(route('cheques.impayes.pay', $impaye), ['date_paiement' => '2026-08-20', 'mode_paiement' => 'virement'])->assertSessionHas('success');

        $impaye->refresh();
        $this->assertSame('paye', $impaye->statut);
        $this->assertSame('2026-08-20', $impaye->date_paiement?->format('Y-m-d'));
        $this->assertSame('virement', $impaye->mode_paiement);
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
            'facture_recue' => false,
            'facture_donnee' => false,
            'montant' => 1200,
            'note' => 'Note',
        ], ...$overrides];
    }
}
