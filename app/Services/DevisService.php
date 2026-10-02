<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleGroup;
use App\Models\Depot;
use App\Models\Devis;
use App\Models\DevisLine;
use App\Models\Fournisseur;
use App\Models\User;
use App\Support\DownloadFilename;
use App\Support\FinancePdf;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DevisService
{
    public function __construct(private readonly OperationService $operations)
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return Devis::query()
            ->with(['fournisseur', 'user', 'lines.article', 'lines.depot'])
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['fournisseur_id'] ?? null, fn ($query, $value) => $query->where('fournisseur_id', $value))
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner
                ->where('reference', 'like', "%{$value}%")
                ->orWhereHas('fournisseur', fn ($q) => $q->where('nom', 'like', "%{$value}%"))))
            ->latest('id')
            ->paginate(100)
            ->withQueryString()
            ->through(fn (Devis $devis) => $this->serialize($devis));
    }

    public function options(): array
    {
        return [
            'fournisseurs' => Fournisseur::query()->whereNull('source')->orderBy('nom')->get(['id', 'nom'])
                ->map(fn (Fournisseur $item) => ['value' => (string) $item->id, 'label' => $item->nom])->all(),
            'groups' => ArticleGroup::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($group) => ['value' => (string) $group->id, 'label' => $group->name])->all(),
            'articles' => Article::query()->orderBy('name')->get(['id', 'reference', 'name', 'group_id'])
                ->map(fn (Article $item) => ['value' => (string) $item->id, 'label' => "{$item->reference} - {$item->display_name}", 'group_id' => (string) ($item->group_id ?? '')])->all(),
        ];
    }

    public function depotOptions(): array
    {
        return Depot::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Depot $item) => ['value' => (string) $item->id, 'label' => $item->name])->all();
    }

    public function create(array $data, User $user): Devis
    {
        return DB::transaction(function () use ($data, $user): Devis {
            $devis = Devis::create([
                'fournisseur_id' => $data['fournisseur_id'],
                'note' => $data['note'] ?? null,
            ]);
            $devis->user_id = $user->id;
            $devis->status = Devis::STATUS_PENDING;
            $devis->reference = sprintf('BC-%s-%04d', now()->format('Ymd'), $devis->id);
            $devis->save();

            foreach (self::mergeLines($data['lines']) as $line) {
                $devis->lines()->create($line);
            }

            return $devis;
        });
    }

    public function addLine(Devis $devis, int $articleId, int $quantity): void
    {
        $this->mutate($devis, function (Devis $devis) use ($articleId, $quantity): void {
            $existing = $devis->lines()->where('article_id', $articleId)->whereNull('validated_at')->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $quantity]);

                return;
            }

            $devis->lines()->create(['article_id' => $articleId, 'quantity' => $quantity]);
        });
    }

    public function updateLine(Devis $devis, DevisLine $line, int $quantity): void
    {
        $this->mutate($devis, function () use ($line, $quantity): void {
            $this->assertOpenLine($line);
            $line->update(['quantity' => $quantity]);
        });
    }

    public function removeLine(Devis $devis, DevisLine $line): void
    {
        $this->mutate($devis, function (Devis $devis) use ($line): void {
            $this->assertOpenLine($line);

            if ($devis->lines()->count() <= 1) {
                throw ValidationException::withMessages(['devis' => 'Un bon de commande doit contenir au moins un article.']);
            }

            $line->delete();
        });
    }

    public function validateLine(Devis $devis, DevisLine $line, int $depotId, ?int $quantity = null): void
    {
        $this->mutate($devis, function (Devis $devis) use ($line, $depotId, $quantity): void {
            $this->assertOpenLine($line);

            if ($quantity !== null) {
                $line->quantity = $quantity;
            }

            $operation = $this->operations->create([
                'type' => 'entree',
                'depot_id' => $depotId,
                'note' => 'Bon de commande '.$devis->reference,
                'lines' => [['article_id' => $line->article_id, 'quantity' => $line->quantity]],
            ]);

            $line->depot_id = $depotId;
            $line->operation_id = $operation->id;
            $line->validated_at = now();
            $line->save();
        });
    }

    public function cancel(Devis $devis): void
    {
        if ($devis->status !== Devis::STATUS_PENDING) {
            throw ValidationException::withMessages(['devis' => 'Ce bon de commande ne peut plus être annulé.']);
        }

        $devis->status = Devis::STATUS_CANCELLED;
        $devis->save();
    }

    public function pdf(Devis $devis): Response
    {
        $devis->load(['fournisseur', 'user', 'lines.article']);

        $rows = $devis->lines->map(fn ($line) => [
            'article' => $line->article?->display_name ?: $line->article?->name ?: '-',
            'unite' => $line->article?->unite ?: 'U',
            'quantite' => $line->quantity,
        ])->all();

        return FinancePdf::a5('pdf.pos-a5', [
            'banner' => 'BON DE COMMANDE',
            'title' => 'Droguerie P',
            'party_name' => $devis->fournisseur?->nom,
            'party_phone' => $devis->fournisseur?->telephone,
            'meta' => [
                ['label' => 'التاريخ :', 'value' => $devis->created_at->format('H:i   Y/m/d')],
                ['label' => 'رقم الطلب :', 'value' => $devis->reference],
            ],
            'columns' => [
                ['key' => 'article', 'label' => 'السلعة', 'width' => '66%'],
                ['key' => 'unite', 'label' => 'الوحدة'],
                ['key' => 'quantite', 'label' => 'الكمية'],
            ],
            'rows' => $rows,
            'stats' => [
                ['label' => ': مجموع المواد', 'value' => (string) $devis->lines->count()],
                ['label' => ': مجموع الكميات', 'value' => (string) $devis->lines->sum('quantity')],
            ],
            'note' => $devis->note,
        ], DownloadFilename::pdf('bon-commande', $devis->reference ?: (string) $devis->id));
    }

    public function serialize(Devis $devis): array
    {
        $lines = $devis->lines->sortBy('id')->values();

        return [
            'id' => $devis->id,
            'reference' => $devis->reference,
            'status' => $devis->status,
            'fournisseur' => $devis->fournisseur?->nom,
            'depots' => $lines->map(fn ($line) => $line->depot?->name)->filter()->unique()->implode(', '),
            'created_by' => $devis->user?->name,
            'note' => $devis->note,
            'created_at' => $devis->created_at?->format('Y-m-d H:i'),
            'validated_at' => $devis->validated_at?->format('Y-m-d H:i'),
            'lines_count' => $lines->count(),
            'validated_count' => $lines->whereNotNull('validated_at')->count(),
            'articles' => $lines->map(fn ($line) => ($line->article?->display_name ?: $line->article?->name).' × '.$line->quantity.($line->validated_at ? ' ✓' : ''))->implode(', '),
            'lines' => $lines->map(fn ($line) => [
                'id' => $line->id,
                'article_id' => (string) $line->article_id,
                'article' => $line->article ? $line->article->reference.' - '.($line->article->display_name ?: $line->article->name) : '-',
                'quantity' => $line->quantity,
                'depot_id' => (string) ($line->depot_id ?? ''),
                'depot' => $line->depot?->name,
                'validated' => $line->validated_at !== null,
            ])->all(),
            'pdf_url' => route('devis.pdf', $devis),
        ];
    }

    private function mutate(Devis $devis, Closure $callback): void
    {
        DB::transaction(function () use ($devis, $callback): void {
            $devis = Devis::query()->lockForUpdate()->findOrFail($devis->id);

            if (! in_array($devis->status, [Devis::STATUS_PENDING, Devis::STATUS_PARTIAL], true)) {
                throw ValidationException::withMessages(['devis' => 'Ce bon de commande ne peut plus être modifié.']);
            }

            $callback($devis);

            $lines = $devis->lines()->get();
            $validated = $lines->whereNotNull('validated_at')->count();

            if ($lines->isNotEmpty() && $validated === $lines->count()) {
                $devis->status = Devis::STATUS_VALIDATED;
                $devis->validated_at = now();
            } else {
                $devis->status = $validated > 0 ? Devis::STATUS_PARTIAL : Devis::STATUS_PENDING;
                $devis->validated_at = null;
            }

            $devis->save();
        });
    }

    private function assertOpenLine(DevisLine $line): void
    {
        if ($line->validated_at !== null) {
            throw ValidationException::withMessages(['devis' => 'Cet article est déjà validé dans un dépôt.']);
        }
    }

    private static function mergeLines(array $lines): array
    {
        return collect($lines)
            ->groupBy('article_id')
            ->map(fn ($group, $articleId) => ['article_id' => (int) $articleId, 'quantity' => (int) $group->sum('quantity')])
            ->values()
            ->all();
    }
}
