<?php

namespace Tests\Feature;

use App\Models\Cheque;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardGlobalTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_dashboard_includes_only_non_sorti_cheques_in_its_financial_donut(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $available = $this->cheque(['numero_cheque' => 'DISPONIBLE', 'montant' => 125.50, 'est_sorti' => false]);
        $this->cheque(['numero_cheque' => 'SORTI', 'montant' => 80, 'est_sorti' => true]);

        $global = $this->dashboardGlobal($admin);

        $this->assertSame(1, $global['kpis']['cheques_disponibles_count']);
        $this->assertSame(125.5, $global['kpis']['cheques_disponibles_total']);
        $this->assertSame([
            ['name' => 'Reste à payer fournisseurs', 'value' => 0, 'color' => '#dc2626'],
            ['name' => 'Reste à recevoir des clients', 'value' => 0, 'color' => '#059669'],
            ['name' => 'Chèques non sortis disponibles', 'value' => 125.5, 'color' => '#7c3aed'],
        ], $global['comparison']);

        $available->update(['est_sorti' => true, 'fournisseur_sortie_nom' => 'Fournisseur test']);

        $global = $this->dashboardGlobal($admin);
        $this->assertSame(0, $global['kpis']['cheques_disponibles_count']);
        $this->assertEquals(0.0, $global['kpis']['cheques_disponibles_total']);
    }

    private function cheque(array $attributes): Cheque
    {
        return Cheque::create([...[
            'type' => 'cheque',
            'client_nom' => 'Client test',
            'tireur_signataire' => 'Signataire',
            'date_emission' => '2026-08-01',
            'date_echeance' => '2026-08-31',
            'statut' => 'en_cours',
            'montant' => 0,
            'est_sorti' => false,
        ], ...$attributes]);
    }

    private function dashboardGlobal(User $admin): array
    {
        $response = $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $page = json_decode($matches[1] ?? '', true);

        return $page['props']['dashboard']['global'];
    }
}
