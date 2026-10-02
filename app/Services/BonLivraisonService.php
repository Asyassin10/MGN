<?php

namespace App\Services;

use App\Models\Article;
use App\Models\BonLivraison;
use App\Models\BonLivraisonLine;
use App\Models\Client;
use App\Models\Depot;
use App\Models\User;
use App\Support\DownloadFilename;
use App\Support\FinancePdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BonLivraisonService
{
    public function __construct(private readonly OperationService $operations)
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return BonLivraison::query()
            ->with(['user', 'employee', 'lines.article', 'lines.depot'])
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
            'clients' => Client::query()->whereNull('source')->orderBy('nom')->get(['id', 'nom'])
                ->map(fn (Client $item) => ['value' => (string) $item->id, 'label' => $item->nom])->all(),
            'depots' => Depot::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Depot $item) => ['value' => (string) $item->id, 'label' => $item->name])->all(),
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($employee) => ['value' => (string) $employee->id, 'label' => $employee->name])->all(),
            'groups' => \App\Models\ArticleGroup::query()->orderBy('name')->get(['id', 'name'])
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
        return DB::transaction(function () use ($data, $user): BonLivraison {
            $client = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;

            $bon = BonLivraison::create([
                'client_id' => $client?->id,
                'client_nom' => $client?->nom ?? ($data['client_nom'] ?? null),
                'client_telephone' => $client?->telephone,
                'employee_id' => $data['employee_id'] ?? null,
                'mode_paiement' => $data['mode_paiement'] ?? 'espece',
                'note' => $data['note'] ?? null,
            ]);
            $bon->user_id = $user->id;
            $bon->reference = sprintf('BL-%s-%04d', now()->format('Ymd'), $bon->id);
            $bon->save();

            $lines = collect($data['lines'])->map(fn (array $line) => $bon->lines()->create([
                'article_id' => $line['article_id'],
                'depot_id' => $line['depot_id'],
                'quantity' => $line['quantity'],
                'unite' => Article::find($line['article_id'])?->unite ?: 'U',
                'price_type' => $line['price_type'],
                'prix' => $line['prix'],
            ]));

            foreach ($lines->groupBy('depot_id') as $depotId => $depotLines) {
                $operation = $this->operations->create([
                    'type' => 'sortie',
                    'depot_id' => (int) $depotId,
                    'employee_id' => $bon->employee_id,
                    'note' => 'Bon de livraison '.$bon->reference,
                    'lines' => $depotLines->groupBy('article_id')
                        ->map(fn ($group, $articleId) => ['article_id' => (int) $articleId, 'quantity' => (int) $group->sum('quantity')])
                        ->values()
                        ->all(),
                ]);

                foreach ($depotLines as $line) {
                    $line->operation_id = $operation->id;
                    $line->save();
                }
            }

            return $bon;
        });
    }

    public function delete(BonLivraison $bon): void
    {
        DB::transaction(function () use ($bon): void {
            $operationIds = $bon->lines()->whereNotNull('operation_id')->pluck('operation_id')->unique();

            foreach ($operationIds as $operationId) {
                $operation = \App\Models\Operation::find($operationId);
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

        $rows = $bon->lines->map(fn (BonLivraisonLine $line) => [
            'total' => $money($line->quantity * (float) $line->prix),
            'prix' => $money((float) $line->prix),
            'depot' => $line->depot?->name,
            'article' => $line->article?->display_name ?: $line->article?->name ?: '-',
            'unite' => $line->unite,
            'quantite' => $line->quantity,
        ])->all();

        return FinancePdf::a5('pdf.pos-a5', [
            'banner' => 'POS',
            'title' => 'Droguerie P',
            'party_name' => $bon->client_nom,
            'party_phone' => $bon->client_telephone,
            'meta' => [
                ['label' => 'التاريخ :', 'value' => $bon->created_at->format('H:i   Y/m/d')],
                ['label' => 'رقم الطلب :', 'value' => $bon->reference],
                ['label' => 'البائع :', 'value' => $userName],
                ['label' => 'المستخدم :', 'value' => $userName],
                ['label' => 'الموصل :', 'value' => $bon->employee?->name ?? '-'],
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
                ['label' => ': مبلغ الخصم', 'value' => '0.00'],
            ],
            'total' => ['label' => 'المجموع', 'value' => $money($total)],
            'payment' => ['amount_label' => 'المبلغ', 'mode_label' => 'طريقة الدفع', 'amount' => $money($total), 'mode' => BonLivraison::PAYMENT_MODES[$bon->mode_paiement] ?? 'Espèce'],
            'note' => $bon->note,
        ], DownloadFilename::pdf('bon-livraison', $bon->reference ?: (string) $bon->id));
    }

    public function serialize(BonLivraison $bon): array
    {
        $total = $bon->lines->sum(fn (BonLivraisonLine $line) => $line->quantity * (float) $line->prix);

        return [
            'id' => $bon->id,
            'reference' => $bon->reference,
            'client' => $bon->client_nom,
            'mode' => BonLivraison::PAYMENT_MODES[$bon->mode_paiement] ?? 'Espèce',
            'employee' => $bon->employee?->name,
            'created_by' => $bon->user?->name,
            'created_at' => $bon->created_at?->format('Y-m-d H:i'),
            'lines_count' => $bon->lines->count(),
            'depots' => $bon->lines->map(fn ($line) => $line->depot?->name)->unique()->implode(', '),
            'total' => round($total, 2),
            'pdf_url' => route('livraisons.pdf', $bon),
        ];
    }
}
