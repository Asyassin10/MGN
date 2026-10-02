<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Depot;
use App\Models\Devis;
use App\Models\Fournisseur;
use App\Models\User;
use App\Support\DownloadFilename;
use App\Support\FinancePdf;
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
            ->with(['fournisseur', 'depot', 'user', 'lines.article'])
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
            'articles' => Article::query()->orderBy('name')->get(['id', 'reference', 'name'])
                ->map(fn (Article $item) => ['value' => (string) $item->id, 'label' => "{$item->reference} - {$item->display_name}"])->all(),
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
            $devis->reference = sprintf('DV-%s-%04d', now()->format('Ymd'), $devis->id);
            $devis->save();

            foreach (self::mergeLines($data['lines']) as $line) {
                $devis->lines()->create($line);
            }

            return $devis;
        });
    }

    public function validate(Devis $devis, int $depotId): void
    {
        DB::transaction(function () use ($devis, $depotId): void {
            $devis = Devis::query()->lockForUpdate()->findOrFail($devis->id);

            if ($devis->status !== Devis::STATUS_PENDING) {
                throw ValidationException::withMessages(['devis' => 'Ce devis ne peut plus être validé.']);
            }

            $operation = $this->operations->create([
                'type' => 'entree',
                'depot_id' => $depotId,
                'note' => 'Devis '.$devis->reference,
                'lines' => $devis->lines->map(fn ($line) => ['article_id' => $line->article_id, 'quantity' => $line->quantity])->all(),
            ]);

            $devis->depot_id = $depotId;
            $devis->operation_id = $operation->id;
            $devis->status = Devis::STATUS_VALIDATED;
            $devis->validated_at = now();
            $devis->save();
        });
    }

    public function cancel(Devis $devis): void
    {
        if ($devis->status !== Devis::STATUS_PENDING) {
            throw ValidationException::withMessages(['devis' => 'Ce devis ne peut plus être annulé.']);
        }

        $devis->status = Devis::STATUS_CANCELLED;
        $devis->save();
    }

    public function pdf(Devis $devis): Response
    {
        $devis->load(['fournisseur', 'depot', 'user', 'lines.article']);
        $userName = $devis->user?->name ?? '-';

        $rows = $devis->lines->map(fn ($line) => [
            'article' => $line->article?->display_name ?: $line->article?->name ?: '-',
            'unite' => $line->article?->unite ?: 'U',
            'quantite' => $line->quantity,
        ])->all();

        return FinancePdf::a5('pdf.pos-a5', [
            'banner' => 'DEVIS',
            'title' => 'Droguerie P',
            'party_name' => $devis->fournisseur?->nom,
            'party_phone' => $devis->fournisseur?->telephone,
            'meta' => [
                ['label' => 'التاريخ :', 'value' => $devis->created_at->format('H:i   Y/m/d')],
                ['label' => 'رقم الطلب :', 'value' => $devis->reference],
                ['label' => 'البائع :', 'value' => $userName],
                ['label' => 'المستخدم :', 'value' => $userName],
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
        ], DownloadFilename::pdf('devis', $devis->reference ?: (string) $devis->id));
    }

    public function serialize(Devis $devis): array
    {
        return [
            'id' => $devis->id,
            'reference' => $devis->reference,
            'status' => $devis->status,
            'fournisseur' => $devis->fournisseur?->nom,
            'depot' => $devis->depot?->name,
            'created_by' => $devis->user?->name,
            'note' => $devis->note,
            'created_at' => $devis->created_at?->format('Y-m-d H:i'),
            'validated_at' => $devis->validated_at?->format('Y-m-d H:i'),
            'lines_count' => $devis->lines->count(),
            'articles' => $devis->lines->map(fn ($line) => ($line->article?->display_name ?: $line->article?->name).' × '.$line->quantity)->implode(', '),
            'pdf_url' => route('devis.pdf', $devis),
        ];
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
