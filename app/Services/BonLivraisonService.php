<?php

namespace App\Services;

use App\Support\CreatedAtFilter;
use App\Models\Article;
use App\Models\ArticleGroup;
use App\Models\BonLivraison;
use App\Models\BonLivraisonLine;
use App\Models\Client;
use App\Models\Depot;
use App\Models\Employee;
use App\Models\Operation;
use App\Models\User;
use App\Support\DownloadFilename;
use App\Support\FinancePdf;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class BonLivraisonService
{
    public function __construct(private readonly OperationService $operations)
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return CreatedAtFilter::apply(BonLivraison::query(), $filters)
            ->with(['user', 'employee', 'client', 'lines.article.depots', 'lines.depot'])
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner
                ->where('reference', 'like', "%{$value}%")
                ->orWhere('client_nom', 'like', "%{$value}%")))
            ->latest('id')
            ->paginate(100)
            ->withQueryString()
            ->through(fn (BonLivraison $bon) => $this->serialize($bon));
    }

    public function options(): array
    {
        return [
            'clients' => Client::query()->whereNull('source')->orderBy('nom')->get(['id', 'nom', 'price_type'])
                ->map(fn (Client $item) => ['value' => (string) $item->id, 'label' => $item->nom, 'price_type' => $item->price_type ?: 'detail'])->all(),
            'depots' => Depot::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Depot $item) => ['value' => (string) $item->id, 'label' => $item->name])->all(),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($employee) => ['value' => (string) $employee->id, 'label' => $employee->name])->all(),
            'groups' => ArticleGroup::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($group) => ['value' => (string) $group->id, 'label' => $group->name])->all(),
            'articles' => Article::query()->with('depots')->orderBy('name')->get()
                ->map(fn (Article $item) => [
                    'value' => (string) $item->id,
                    'label' => "{$item->reference} - {$item->display_name}",
                    'group_id' => (string) ($item->group_id ?? ''),
                    'stocks' => $item->depots->mapWithKeys(fn ($depot) => [(string) $depot->id => (int) $depot->pivot->quantity])->all(),
                    'unite' => $item->unite,
                    'prix_detail_ht' => (float) $item->prix_detail_ht,
                    'prix_demi_gros_ht' => (float) $item->prix_demi_gros_ht,
                    'prix_gros_ht' => (float) $item->prix_gros_ht,
                    'prix_special_ht' => (float) $item->prix_special_ht,
                    'prix_min' => (float) $item->prix_min,
                    'prix_max' => (float) $item->prix_max,
                ])->all(),
        ];
    }

    public function create(array $data, User $user): BonLivraison
    {
        $this->assertPricesWithinLimits($data['lines']);

        return DB::transaction(function () use ($data, $user): BonLivraison {
            $client = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;

            $bon = BonLivraison::create([
                'client_id' => $client?->id,
                'client_nom' => $client?->nom ?? ($data['client_nom'] ?? null),
                'client_telephone' => $client?->telephone,
                'livreur_nom' => filled($data['livreur_nom'] ?? null) ? trim($data['livreur_nom']) : null,
                'mode_paiement' => $data['mode_paiement'] ?? 'espece',
                'note' => $data['note'] ?? null,
            ]);
            $bon->user_id = $user->id;
            $bon->status = BonLivraison::STATUS_PENDING;
            $bon->reference = sprintf('DV-%s-%04d', now()->format('Ymd'), $bon->id);
            $bon->save();

            foreach ($data['lines'] as $line) {
                $this->createLine($bon, $line);
            }

            return $bon;
        });
    }

    public function addLine(BonLivraison $bon, array $line): void
    {
        $this->assertPricesWithinLimits([$line]);

        $this->mutate($bon, fn (BonLivraison $bon) => $this->createLine($bon, $line));
    }

    public function updateLine(BonLivraison $bon, BonLivraisonLine $line, array $changes): void
    {
        $this->mutate($bon, function () use ($line, $changes): void {
            $this->assertOpenLine($line);

            $values = collect($changes)->only(['quantity', 'prix', 'price_type'])->filter(fn ($value) => $value !== null)->all();
            $this->assertPricesWithinLimits([['article_id' => $line->article_id, 'prix' => $values['prix'] ?? $line->prix]]);
            $line->update($values);
        });
    }

    public function removeLine(BonLivraison $bon, BonLivraisonLine $line): void
    {
        $this->mutate($bon, function (BonLivraison $bon) use ($line): void {
            $this->assertOpenLine($line);

            if ($bon->lines()->count() <= 1) {
                throw ValidationException::withMessages(['devis' => 'Un devis doit contenir au moins un article.']);
            }

            $line->delete();
        });
    }

    public function validateLine(BonLivraison $bon, BonLivraisonLine $line, ?int $quantity = null, ?float $prix = null): void
    {
        $this->mutate($bon, function (BonLivraison $bon) use ($line, $quantity, $prix): void {
            $this->assertOpenLine($line);

            if ($quantity !== null) {
                $line->quantity = $quantity;
            }

            if ($prix !== null) {
                $line->prix = $prix;
            }

            $this->assertPricesWithinLimits([['article_id' => $line->article_id, 'prix' => $line->prix]]);

            $operation = $this->operations->create([
                'type' => 'sortie',
                'depot_id' => $line->depot_id,
                'note' => 'Devis '.$bon->reference,
                'lines' => [['article_id' => $line->article_id, 'quantity' => $line->quantity]],
            ]);

            $line->operation_id = $operation->id;
            $line->validated_at = now();
            $line->save();
        });
    }

    public function updateHeader(BonLivraison $bon, array $data): void
    {
        $this->mutate($bon, function (BonLivraison $bon) use ($data): void {
            $bon->livreur_nom = filled($data['livreur_nom'] ?? null) ? trim($data['livreur_nom']) : null;
            $bon->mode_paiement = $data['mode_paiement'];
            $bon->save();
        });
    }

    public function cancel(BonLivraison $bon): void
    {
        if ($bon->status !== BonLivraison::STATUS_PENDING) {
            throw ValidationException::withMessages(['devis' => 'Ce devis ne peut plus être annulé.']);
        }

        $bon->status = BonLivraison::STATUS_CANCELLED;
        $bon->save();
    }

    public function delete(BonLivraison $bon): void
    {
        DB::transaction(function () use ($bon): void {
            $operationIds = $bon->lines()->whereNotNull('operation_id')->pluck('operation_id')->unique();

            foreach ($operationIds as $operationId) {
                $operation = Operation::find($operationId);
                if ($operation) {
                    $this->operations->delete($operation);
                }
            }

            $bon->delete();
        });
    }

    public function pdf(BonLivraison $bon): Response
    {
        $bon->load(['user', 'employee', 'lines.article', 'lines.depot']);
        $userName = $bon->user?->name ?? '-';
        $money = fn (float $value) => number_format($value, 2, '.', '');
        $total = $bon->lines->sum(fn (BonLivraisonLine $line) => $line->quantity * (float) $line->prix);
        $weight = $bon->lines->sum(fn (BonLivraisonLine $line) => $line->quantity * (float) ($line->article?->poids ?? 0));

        $rows = $bon->lines->sortBy('id')->map(fn (BonLivraisonLine $line) => [
            'total' => $money($line->quantity * (float) $line->prix),
            'prix' => $money((float) $line->prix),
            'depot' => $line->depot?->name,
            'article' => $line->article?->display_name ?: $line->article?->name ?: '-',
            'unite' => $line->unite,
            'quantite' => $line->quantity,
        ])->values()->all();

        return FinancePdf::a5('pdf.pos-a5', [
            'banner' => 'DEVIS NAKHIL PEINTURE',
            'title' => '',
            'party_name' => $bon->client_nom,
            'party_phone' => $bon->client_telephone,
            'meta' => [
                ['label' => 'التاريخ :', 'value' => $bon->created_at->format('H:i   Y/m/d')],
                ['label' => 'رقم الطلب :', 'value' => $bon->reference],
                ['label' => 'البائع :', 'value' => mb_strtoupper($userName)],
                ['label' => 'الموصل :', 'value' => $bon->livreur_nom ?: ($bon->employee?->name ?? '-')],
            ],
            'columns' => [
                ['key' => 'total', 'label' => 'المجموع'],
                ['key' => 'prix', 'label' => 'السعر'],
                ['key' => 'depot', 'label' => 'المستودع'],
                ['key' => 'article', 'label' => 'السلعة', 'width' => '34%'],
                ['key' => 'unite', 'label' => 'الوحدة'],
                ['key' => 'quantite', 'label' => 'الكمية'],
            ],
            'rows' => $rows,
            'stats' => [
                ['label' => ': مجموع المواد', 'value' => (string) $bon->lines->count()],
                ['label' => ': الوزن الإجمالي(كيلو)', 'value' => number_format($weight, 3, '.', '')],
            ],
            'payment' => ['amount_label' => 'المبلغ', 'mode_label' => 'طريقة الدفع', 'amount' => $money($total), 'mode' => BonLivraison::PAYMENT_MODES[$bon->mode_paiement] ?? 'Espèce'],
            'note' => $bon->note,
        ], DownloadFilename::pdf('devis', $bon->reference ?: (string) $bon->id));
    }

    public function serialize(BonLivraison $bon): array
    {
        $lines = $bon->lines->sortBy('id')->values();
        $total = $lines->sum(fn (BonLivraisonLine $line) => $line->quantity * (float) $line->prix);

        return [
            'id' => $bon->id,
            'reference' => $bon->reference,
            'status' => $bon->status,
            'client' => $bon->client_nom,
            'client_price_type' => $bon->client?->price_type ?: 'detail',
            'mode' => BonLivraison::PAYMENT_MODES[$bon->mode_paiement] ?? 'Espèce',
            'mode_paiement' => $bon->mode_paiement,
            'livreur_nom' => $bon->livreur_nom ?? '',
            'employee' => $bon->livreur_nom ?: $bon->employee?->name,
            'created_by' => $bon->user?->name,
            'created_at' => $bon->created_at?->format('Y-m-d H:i'),
            'lines_count' => $lines->count(),
            'validated_count' => $lines->whereNotNull('validated_at')->count(),
            'depots' => $lines->map(fn ($line) => $line->depot?->name)->filter()->unique()->implode(', '),
            'articles' => $lines->map(fn ($line) => ($line->article?->display_name ?: $line->article?->name).' × '.$line->quantity.($line->validated_at ? ' ✓' : ''))->implode(', '),
            'lines' => $lines->map(fn (BonLivraisonLine $line) => [
                'id' => $line->id,
                'article_id' => (string) $line->article_id,
                'article' => $line->article ? $line->article->reference.' - '.($line->article->display_name ?: $line->article->name) : '-',
                'depot_id' => (string) $line->depot_id,
                'depot' => $line->depot?->name,
                'quantity' => $line->quantity,
                'prix' => (float) $line->prix,
                'price_type' => $line->price_type,
                'prix_min' => (float) ($line->article?->prix_min ?? 0),
                'prix_max' => (float) ($line->article?->prix_max ?? 0),
                'stock' => (int) ($line->article?->depots->firstWhere('id', $line->depot_id)?->pivot->quantity ?? 0),
                'validated' => $line->validated_at !== null,
            ])->all(),
            'total' => round($total, 2),
            'pdf_url' => route('livraisons.pdf', $bon),
        ];
    }

    private function createLine(BonLivraison $bon, array $line): void
    {
        $bon->lines()->create([
            'article_id' => $line['article_id'],
            'depot_id' => $line['depot_id'],
            'quantity' => $line['quantity'],
            'unite' => Article::find($line['article_id'])?->unite ?: 'U',
            'price_type' => $line['price_type'],
            'prix' => $line['prix'],
        ]);
    }

    private function mutate(BonLivraison $bon, Closure $callback): void
    {
        DB::transaction(function () use ($bon, $callback): void {
            $bon = BonLivraison::query()->lockForUpdate()->findOrFail($bon->id);

            if (! in_array($bon->status, [BonLivraison::STATUS_PENDING, BonLivraison::STATUS_PARTIAL], true)) {
                throw ValidationException::withMessages(['devis' => 'Ce devis ne peut plus être modifié.']);
            }

            $callback($bon);

            $lines = $bon->lines()->get();
            $validated = $lines->whereNotNull('validated_at')->count();

            if ($lines->isNotEmpty() && $validated === $lines->count()) {
                $bon->status = BonLivraison::STATUS_VALIDATED;
                $bon->validated_at = now();
            } else {
                $bon->status = $validated > 0 ? BonLivraison::STATUS_PARTIAL : BonLivraison::STATUS_PENDING;
                $bon->validated_at = null;
            }

            $bon->save();
        });
    }

    private function assertOpenLine(BonLivraisonLine $line): void
    {
        if ($line->validated_at !== null) {
            throw ValidationException::withMessages(['devis' => 'Cet article est déjà validé : le stock a été retiré.']);
        }
    }

    private function assertPricesWithinLimits(array $lines): void
    {
        foreach ($lines as $line) {
            $article = Article::find($line['article_id']);

            if (! $article) {
                continue;
            }

            $prix = (float) $line['prix'];
            $max = (float) $article->prix_max;
            $min = (float) $article->prix_min;
            $name = $article->reference.' - '.($article->display_name ?: $article->name);

            if ($max > 0 && $prix > $max) {
                throw ValidationException::withMessages(['lines' => "Prix maximum pour {$name} : ".number_format($max, 2, ',', ' ').' MAD.']);
            }

            if ($min > 0 && $prix < $min) {
                throw ValidationException::withMessages(['lines' => "Prix minimum pour {$name} : ".number_format($min, 2, ',', ' ').' MAD.']);
            }
        }
    }
}
