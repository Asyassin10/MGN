<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Bank;
use App\Models\Cheque;
use App\Models\ChequeClient;
use App\Models\ChequeFournisseur;
use App\Models\ChequePartyClient;
use App\Models\ChequePartyFournisseur;
use App\Models\Client;
use App\Models\ClientEntry;
use App\Models\ClientPayment;
use App\Models\Depot;
use App\Models\Employee;
use App\Models\Fournisseur;
use App\Models\FournisseurCheque;
use App\Models\FournisseurFacture;
use App\Models\FournisseurPayment;
use App\Models\FournisseurReleveCompte;
use App\Models\Operation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Disposable records for manually testing every business area.
 *
 * Every record is marked with the DEMO prefix so it can be removed safely
 * after manual testing without touching normal business data.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            [$depot, $articles, $employee] = $this->seedDepot();
            [$fournisseurs, $clients] = $this->seedParties();
            $banks = $this->seedBanks();

            $this->seedOperations($depot, $articles, $employee);
            $this->seedSupplierActivity($fournisseurs, $banks);
            $this->seedClientActivity($clients);
            $this->seedCheques($fournisseurs, $clients, $banks);
        });
    }

    private function seedDepot(): array
    {
        $depot = Depot::query()->updateOrCreate(
            ['name' => 'DEMO - Dépôt Test'],
            ['location' => 'Casablanca - Zone de test'],
        );

        $employee = Employee::query()->updateOrCreate(
            ['name' => 'DEMO Responsable'],
            ['prenom' => 'Test', 'poste' => 'Magasinier', 'telephone' => '0600000001'],
        );

        $articleData = [
            ['reference' => 'DEMO-ART-001', 'name' => 'Produit de démonstration A', 'quantity' => 125],
            ['reference' => 'DEMO-ART-002', 'name' => 'Produit de démonstration B', 'quantity' => 80],
            ['reference' => 'DEMO-ART-003', 'name' => 'Produit de démonstration C', 'quantity' => 42],
        ];

        $articles = collect($articleData)->map(function (array $data): Article {
            return Article::query()->updateOrCreate(
                ['reference' => $data['reference']],
                ['name' => $data['name']],
            );
        });

        $depot->articles()->syncWithoutDetaching(
            $articles->mapWithKeys(fn (Article $article, int $index) => [
                $article->id => ['quantity' => $articleData[$index]['quantity']],
            ])->all(),
        );

        return [$depot, $articles, $employee];
    }

    private function seedOperations(Depot $depot, $articles, Employee $employee): void
    {
        $operations = [
            [
                'reference' => 'DEMO-OP-ENTREE-001',
                'type' => 'entree',
                'note' => 'Réception de stock de démonstration.',
                'lines' => [[0, 150], [1, 100], [2, 60]],
            ],
            [
                'reference' => 'DEMO-OP-SORTIE-001',
                'type' => 'sortie',
                'note' => 'Sortie de stock de démonstration.',
                'lines' => [[0, 25], [1, 20], [2, 18]],
            ],
        ];

        foreach ($operations as $data) {
            $operation = Operation::query()->updateOrCreate(
                ['reference' => $data['reference']],
                [
                    'type' => $data['type'],
                    'depot_id' => $depot->id,
                    'employee_id' => $employee->id,
                    'note' => $data['note'],
                ],
            );

            $operation->lines()->delete();
            foreach ($data['lines'] as [$articleIndex, $quantity]) {
                $article = $articles->get($articleIndex);
                $operation->lines()->create([
                    'article_id' => $article->id,
                    'reference' => $article->reference,
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    private function seedParties(): array
    {
        $fournisseurs = collect([
            ['nom' => 'DEMO - Atlas Fournitures', 'telephone' => '0522000001', 'ville' => 'Casablanca'],
            ['nom' => 'DEMO - Nord Distribution', 'telephone' => '0539000002', 'ville' => 'Tanger'],
            ['nom' => 'DEMO - Sud Matériaux', 'telephone' => '0524000003', 'ville' => 'Marrakech'],
        ])->map(fn (array $data) => Fournisseur::query()->updateOrCreate(
            ['nom' => $data['nom']],
            [...$data, 'note' => 'Fournisseur de démonstration.'],
        ));

        $clients = collect([
            ['nom' => 'DEMO - Client Alpha', 'telephone' => '0611000001', 'ville' => 'Casablanca'],
            ['nom' => 'DEMO - Client Beta', 'telephone' => '0622000002', 'ville' => 'Rabat'],
            ['nom' => 'DEMO - Client Gamma', 'telephone' => '0633000003', 'ville' => 'Marrakech'],
        ])->map(fn (array $data) => Client::query()->updateOrCreate(
            ['nom' => $data['nom']],
            [...$data, 'note' => 'Client de démonstration.'],
        ));

        return [$fournisseurs, $clients];
    }

    private function seedBanks()
    {
        return collect(['DEMO Banque Populaire', 'DEMO Attijariwafa Bank', 'DEMO CIH Bank'])
            ->mapWithKeys(fn (string $name) => [$name => Bank::query()->updateOrCreate(['name' => $name])]);
    }

    private function seedSupplierActivity($fournisseurs, $banks): void
    {
        foreach ($fournisseurs as $index => $fournisseur) {
            $sequence = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $releve = FournisseurReleveCompte::query()->updateOrCreate(
                ['fournisseur_id' => $fournisseur->id, 'code_client' => "DEMO-FR-{$sequence}"],
                ['date_releve' => now()->subDays(15 - $index), 'note' => 'Relevé fournisseur de démonstration.'],
            );

            FournisseurFacture::query()->updateOrCreate(
                ['fournisseur_releve_compte_id' => $releve->id, 'numero_facture' => "DEMO-FACT-{$sequence}"],
                [
                    'fournisseur_id' => $fournisseur->id,
                    'date_facture' => now()->subDays(20 - $index),
                    'montant' => 2500 + ($index * 750),
                    'note' => 'Facture de démonstration.',
                ],
            );

            FournisseurPayment::query()->updateOrCreate(
                ['reference' => "DEMO-FP-{$sequence}"],
                [
                    'fournisseur_id' => $fournisseur->id,
                    'fournisseur_releve_compte_id' => $releve->id,
                    'date_paiement' => now()->subDays(10 - $index),
                    'montant' => 900 + ($index * 200),
                    'mode' => $index === 1 ? 'virement' : 'espece',
                    'note' => 'Paiement de démonstration.',
                ],
            );

            FournisseurCheque::query()->updateOrCreate(
                ['numero_cheque' => "DEMO-FC-{$sequence}"],
                [
                    'fournisseur_id' => $fournisseur->id,
                    'type' => $index === 2 ? 'effet' : 'cheque',
                    'banque' => $banks->values()->get($index)->name,
                    'montant' => 750 + ($index * 150),
                    'motif' => 'Règlement de démonstration.',
                    'tireur_signataire' => 'Droguerie P',
                    'date_emission' => now()->subDays(5 - $index),
                    'date_echeance' => now()->addDays(12 + ($index * 8)),
                    'statut' => ['en_cours', 'en_caisse', 'impaye'][$index],
                    'note' => 'Chèque fournisseur de démonstration.',
                ],
            );
        }
    }

    private function seedClientActivity($clients): void
    {
        foreach ($clients as $index => $client) {
            $sequence = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

            ClientEntry::query()->updateOrCreate(
                ['client_id' => $client->id, 'description' => "DEMO - Vente {$sequence}"],
                ['date_entree' => now()->subDays(12 - $index), 'montant' => 1800 + ($index * 650)],
            );

            ClientPayment::query()->updateOrCreate(
                ['reference' => "DEMO-CP-{$sequence}"],
                [
                    'client_id' => $client->id,
                    'date_paiement' => now()->subDays(6 - $index),
                    'montant' => 700 + ($index * 300),
                    'mode' => $index === 1 ? 'cheque' : 'espece',
                    'note' => 'Paiement client de démonstration.',
                ],
            );
        }
    }

    private function seedCheques($fournisseurs, $clients, $banks): void
    {
        $clientParties = collect([
            ['nom' => 'DEMO - Tireur Client A', 'telephone' => '0644000001', 'email' => 'client-a.demo@example.test'],
            ['nom' => 'DEMO - Tireur Client B', 'telephone' => '0644000002', 'email' => 'client-b.demo@example.test'],
            ['nom' => 'DEMO - Tireur Client C', 'telephone' => '0644000003', 'email' => 'client-c.demo@example.test'],
        ])->map(fn (array $data) => ChequePartyClient::query()->updateOrCreate(['nom' => $data['nom']], $data));

        $fournisseurParties = collect([
            ['nom' => 'DEMO - Bénéficiaire A', 'telephone' => '0655000001', 'email' => 'fournisseur-a.demo@example.test'],
            ['nom' => 'DEMO - Bénéficiaire B', 'telephone' => '0655000002', 'email' => 'fournisseur-b.demo@example.test'],
            ['nom' => 'DEMO - Bénéficiaire C', 'telephone' => '0655000003', 'email' => 'fournisseur-c.demo@example.test'],
        ])->map(fn (array $data) => ChequePartyFournisseur::query()->updateOrCreate(['nom' => $data['nom']], $data));

        foreach ([0, 1, 2] as $index) {
            $sequence = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $bank = $banks->values()->get($index);

            ChequeClient::query()->updateOrCreate(
                ['numero_cheque' => "DEMO-CC-{$sequence}"],
                [
                    'type' => $index === 2 ? 'effet' : 'cheque',
                    'client_id' => $clientParties->get($index)->id,
                    'bank_id' => $bank->id,
                    'banque' => $bank->name,
                    'montant' => 1300 + ($index * 400),
                    'motif' => 'Encaissement de démonstration.',
                    'tireur_signataire' => $clientParties->get($index)->nom,
                    'date_emission' => now()->subDays(4 - $index),
                    'date_echeance' => now()->addDays(7 + ($index * 9)),
                    'statut' => ['en_cours', 'en_caisse', 'impaye'][$index],
                    'facture_recue' => $index === 1 ? true : ($index === 2 ? null : false),
                ],
            );

            ChequeFournisseur::query()->updateOrCreate(
                ['numero_cheque' => "DEMO-CF-{$sequence}"],
                [
                    'type' => $index === 1 ? 'effet' : 'cheque',
                    'fournisseur_id' => $fournisseurParties->get($index)->id,
                    'bank_id' => $bank->id,
                    'banque' => $bank->name,
                    'montant' => 1600 + ($index * 350),
                    'motif' => 'Décaissement de démonstration.',
                    'tireur_signataire' => 'Droguerie P',
                    'date_emission' => now()->subDays(3 - $index),
                    'date_echeance' => now()->addDays(10 + ($index * 10)),
                    'statut' => ['en_cours', 'en_caisse', 'impaye'][$index],
                    'facture_recue' => $index === 0 ? null : true,
                ],
            );
        }

        Cheque::query()->updateOrCreate(
            ['numero_cheque' => 'DEMO-STANDALONE-CLIENT-001'],
            [
                'type' => 'client',
                'tier_id' => $clients->first()->id,
                'tier_type' => Client::class,
                'banque' => $banks->first()->name,
                'tireur_signataire' => $clients->first()->nom,
                'montant' => 2200,
                'date_emission' => now()->subDays(2),
                'date_echeance' => now()->addDays(14),
                'statut' => 'en_cours',
                'facture_recue' => true,
                'note' => 'Chèque standalone client de démonstration.',
            ],
        );

        Cheque::query()->updateOrCreate(
            ['numero_cheque' => 'DEMO-STANDALONE-FOURNISSEUR-001'],
            [
                'type' => 'fournisseur',
                'tier_id' => $fournisseurs->first()->id,
                'tier_type' => Fournisseur::class,
                'banque' => $banks->get('DEMO Attijariwafa Bank')->name,
                'tireur_signataire' => 'Droguerie P',
                'montant' => 3100,
                'date_emission' => now()->subDays(1),
                'date_echeance' => now()->addDays(21),
                'statut' => 'encaisse',
                'facture_recue' => false,
                'note' => 'Chèque standalone fournisseur de démonstration.',
            ],
        );
    }
}
