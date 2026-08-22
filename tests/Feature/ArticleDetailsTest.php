<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Depot;
use App\Models\Operation;
use App\Models\OperationLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_details_show_total_stock_by_depot_and_only_its_operations(): void
    {
        $depotA = Depot::create(['name' => 'Dépôt A', 'location' => 'Casablanca']);
        $depotB = Depot::create(['name' => 'Dépôt B', 'location' => 'Rabat']);
        $article = Article::create(['reference' => 'ART-01', 'name' => 'Peinture blanche']);
        $otherArticle = Article::create(['reference' => 'ART-02', 'name' => 'Pinceau']);
        $article->depots()->sync([$depotA->id => ['quantity' => 7], $depotB->id => ['quantity' => 5]]);

        $operation = Operation::create(['reference' => 'OP-001', 'type' => 'entree', 'depot_id' => $depotA->id, 'note' => 'Réception']);
        OperationLine::create(['operation_id' => $operation->id, 'article_id' => $article->id, 'reference' => 'ART-01', 'quantity' => 4]);
        OperationLine::create(['operation_id' => $operation->id, 'article_id' => $otherArticle->id, 'reference' => 'ART-02', 'quantity' => 2]);

        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('articles.show', $article))->assertOk();
        preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $page = json_decode($matches[1] ?? '', true);

        $this->assertSame('ART-01', $page['props']['article']['reference']);
        $this->assertSame('Peinture blanche', $page['props']['article']['name']);
        $this->assertSame(12, $page['props']['article']['total_quantity']);
        $this->assertSame(['Dépôt A', 'Dépôt B'], collect($page['props']['depots'])->pluck('name')->all());
        $this->assertSame([7, 5], collect($page['props']['depots'])->pluck('quantity')->all());
        $this->assertCount(1, $page['props']['operations']['data']);
        $this->assertSame('OP-001', $page['props']['operations']['data'][0]['reference']);
        $this->assertSame('entree', $page['props']['operations']['data'][0]['type']);

        $indexResponse = $this->actingAs($admin)->get(route('articles.index'))->assertOk();
        preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $indexResponse->getContent(), $matches);
        $indexPage = json_decode($matches[1] ?? '', true);
        $row = collect($indexPage['props']['articles']['data'])->firstWhere('id', $article->id);
        $this->assertSame(12, $row['total_quantity']);
    }
}
