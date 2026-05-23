<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@abd.local'],
            [
                'name' => 'Administrateur Droguerie P',
                'password' => 'password',
                'pin' => '123456',
            ],
        );

        $now = now();
        $articleRows = collect($this->articleCatalog())
            ->unique('reference')
            ->map(fn ($data) => [
                'reference' => $data['reference'],
                'name' => $data['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values();

        Article::query()->upsert($articleRows->all(), ['reference'], ['name', 'updated_at']);

        $articles = Article::query()
            ->whereIn('reference', $articleRows->pluck('reference'))
            ->get(['id', 'reference']);

        $depots = collect([
            ['name' => 'Dépôt Central', 'location' => 'Casablanca'],
            ['name' => 'Dépôt Nord', 'location' => 'Tanger'],
            ['name' => 'Dépôt Sud', 'location' => 'Marrakech'],
        ])->map(fn ($data) => Depot::query()->updateOrCreate(
            ['name' => $data['name']],
            ['location' => $data['location']],
        ));

        $assignments = $depots
            ->flatMap(fn (Depot $depot) => $articles->map(fn (Article $article) => [
                'depot_id' => $depot->id,
                'article_id' => $article->id,
                'quantity' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]))
            ->values();

        // Seed missing availability without overwriting live depot quantities.
        DB::table('depot_article')->insertOrIgnore($assignments->all());
    }

    private function articleCatalog(): array
    {
        $path = database_path('data/articles.csv');

        if (! file_exists($path)) {
            throw new \RuntimeException('Le catalogue articles est introuvable : '.$path);
        }

        return collect(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->map(function (string $line): array {
                [$reference, $name] = array_pad(explode('|', $line, 2), 2, '');

                return ['reference' => trim($reference), 'name' => trim($name)];
            })
            ->filter(fn (array $article) => $article['reference'] !== '' && $article['name'] !== '')
            ->values()
            ->all();
    }
}
