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

        DB::table('depot_article')->upsert(
            $assignments->all(),
            ['depot_id', 'article_id'],
            ['quantity', 'updated_at'],
        );
    }

    private function articleCatalog(): array
    {
        $path = '/Users/mac/Downloads/ETAT SL3A 2026.docx';

        if (class_exists(\ZipArchive::class) && file_exists($path)) {
            $zip = new \ZipArchive();

            if ($zip->open($path) === true) {
                $xml = $zip->getFromName('word/document.xml') ?: '';
                $zip->close();

                $text = preg_replace('/<w:tab\\/>/', ' ', $xml);
                $text = preg_replace('/<\\/w:p>/', "\n", (string) $text);
                $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_XML1, 'UTF-8');
                $lines = collect(preg_split('/\\R+/', $text))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->values();

                $skip = ['سعر الجملة', 'سعر التقسيط', 'سعر الشراء', 'السلعة', 'كود', 'لائحة السلعة', 'POS'];
                $articles = [];

                for ($i = 1; $i < $lines->count(); $i++) {
                    $code = $lines[$i];
                    $name = $lines[$i - 1];

                    if (preg_match('/^\\d{5}$/', $code) && ! in_array($name, $skip, true) && ! isset($articles[$code])) {
                        $cleanName = @iconv('UTF-8', 'UTF-8//IGNORE', $name) ?: preg_replace('/[^\x20-\x7E\p{Arabic}\p{N}\s.,:;()\\-\\/]/u', '', $name);
                        $articles[$code] = ['reference' => $code, 'name' => trim($cleanName)];
                    }
                }

                if ($articles !== []) {
                    return array_values($articles);
                }
            }
        }

        return [
            ['reference' => '00001', 'name' => 'لندوي اطلس 25كلغ'],
            ['reference' => '00021', 'name' => 'إتري بلاست 03 كلغ'],
            ['reference' => '00022', 'name' => 'إتري بلاست 5كلغ'],
            ['reference' => '00023', 'name' => 'إتري بلاست 10كلغ'],
            ['reference' => '00025', 'name' => 'إتري بلاست 1كلغ'],
        ];
    }
}
