<?php

namespace Tests\Feature;

use App\Models\ChequeClient;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPaymentMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_page_combines_normal_payments_and_cheques_without_a_collection_error(): void
    {
        $client = Client::create(['nom' => 'Client test']);
        ClientPayment::create(['client_id' => $client->id, 'date_paiement' => '2026-08-23', 'montant' => 100, 'mode' => 'espece']);
        ChequeClient::create([
            'client_id' => $client->id,
            'type' => 'cheque',
            'numero_cheque' => 'CH-001',
            'montant' => 250,
            'date_emission' => '2026-08-22',
            'date_echeance' => '2026-08-30',
            'statut' => 'en_cours',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('clients.show', $client))->assertOk();
        preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $page = json_decode($matches[1] ?? '', true);
        $payments = $page['props']['payments']['data'];

        $this->assertCount(2, $payments);
        $this->assertSame(['cheque', 'payment'], collect($payments)->pluck('record_type')->sort()->values()->all());
    }
}
