<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Depot;
use App\Models\Employee;
use App\Models\Operation;
use App\Support\ExcelExport;
use App\Support\FinancePdf;
use App\Support\DownloadFilename;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest()
            ->paginate(100)
            ->withQueryString()
            ->through(fn (Operation $operation) => [
                'id' => $operation->id,
                'reference' => $operation->reference,
                'type' => $operation->type,
                'depot' => $operation->depot->name,
                'employee' => $operation->employee?->name,
                'lines_count' => $operation->lines->count(),
                'created_at' => $operation->created_at->format('Y-m-d H:i'),
                'show_url' => route('operations.show', $operation),
            ]);
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)
            ->latest()
            ->get()
            ->map(fn (Operation $operation) => [
                $operation->reference,
                $operation->created_at->format('Y-m-d H:i'),
                $operation->type,
                $operation->depot->name,
                $operation->employee?->name,
                $operation->lines->count(),
            ]);

        return ExcelExport::download('operations-export', ['Reference', 'Date', 'Type', 'Depot', 'Employe', 'Lignes'], $rows);
    }

    public function filterOptions(): array
    {
        return [
            'depots' => Depot::query()->orderBy('name')->get(['id', 'name'])->map(fn ($depot) => ['value' => (string) $depot->id, 'label' => $depot->name]),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn ($employee) => ['value' => (string) $employee->id, 'label' => $employee->name]),
        ];
    }

    public function show(Operation $operation): array
    {
        $operation->load(['depot', 'employee', 'lines.article']);

        return [
            'id' => $operation->id,
            'reference' => $operation->reference,
            'type' => $operation->type,
            'note' => $operation->note,
            'created_at' => $operation->created_at->format('d/m/Y H:i'),
            'depot' => $operation->depot?->only('id', 'name'),
            'employee' => $operation->employee?->only('id', 'name'),
            'lines' => $operation->lines
                ->sortByDesc('id')
                ->values()
                ->map(fn ($line) => [
                    'id' => $line->id,
                    'reference' => $line->reference,
                    'article_name' => $line->article?->display_name ?: $line->article?->name ?: '-',
                    'quantity' => $line->quantity,
                ])
                ->all(),
            'pdf_url' => route('operations.pdf', $operation),
            'excel_url' => route('operations.show', ['operation' => $operation->id, 'export' => 1]),
        ];
    }

    public function form(Operation $operation): array
    {
        $operation->load('lines');

        return [
            'id' => $operation->id,
            'reference' => $operation->reference,
            'type' => $operation->type,
            'depot_id' => (string) $operation->depot_id,
            'employee_id' => $operation->employee_id ? (string) $operation->employee_id : '',
            'note' => $operation->note ?? '',
            'lines' => $operation->lines->map(fn ($line) => [
                'article_id' => (string) $line->article_id,
                'quantity' => (int) $line->quantity,
            ])->values()->all(),
        ];
    }

    public function exportLines(Operation $operation): StreamedResponse
    {
        $operation->load(['lines.article']);

        $rows = $operation->lines
            ->sortByDesc('id')
            ->map(fn ($line) => [
                $line->reference,
                $line->article?->display_name ?: $line->article?->name,
                $line->quantity,
            ]);

        return ExcelExport::download('operation-'.$operation->id.'-lignes-export', ['Reference', 'Article', 'Quantite'], $rows);
    }

    public function pdf(Operation $operation): Response
    {
        $operation->load(['depot', 'employee', 'lines.article']);

        return FinancePdf::preview([
            'title' => 'Operation '.$operation->reference,
            'subtitle' => 'Bon de commande',
            'brand' => 'Droguerie Palmeraie',
            'meta' => [
                'Reference operation' => $operation->reference,
                'Date' => $operation->created_at->format('d/m/Y H:i'),
                'Depot' => $operation->depot?->name,
                'Employe' => $operation->employee?->name,
            ],
            'columns' => [
                ['key' => 'reference', 'label' => 'Reference'],
                ['key' => 'designation', 'label' => 'Designation'],
                ['key' => 'quantity', 'label' => 'Quantite', 'align' => 'right'],
            ],
            'rows' => $operation->lines
                ->sortByDesc('id')
                ->values()
                ->map(fn ($line) => [
                    'reference' => $line->reference,
                    'designation' => $line->article?->display_name ?: $line->article?->name ?: '-',
                    'quantity' => $line->quantity,
                ])
                ->all(),
            'note' => $operation->note,
        ], DownloadFilename::pdf('operation', $operation->reference ?: (string) $operation->id));
    }

    public function create(array $payload): Operation
    {
        return DB::transaction(function () use ($payload): Operation {
            $operation = Operation::create([
                'type' => $payload['type'],
                'depot_id' => $payload['depot_id'],
                'employee_id' => $payload['employee_id'] ?? null,
                'note' => $payload['note'] ?? null,
            ]);

            $operation->update(['reference' => sprintf('OP-%s-%04d', now()->format('Ymd'), $operation->id)]);

            $this->applyMovement($operation, $payload);

            return $operation->fresh(['depot', 'employee', 'lines.article']);
        });
    }

    public function update(Operation $operation, array $payload): Operation
    {
        return DB::transaction(function () use ($operation, $payload): Operation {
            $this->reverseMovement($operation);
            $operation->update([
                'type' => $payload['type'],
                'depot_id' => $payload['depot_id'],
                'employee_id' => $payload['employee_id'] ?? null,
                'note' => $payload['note'] ?? null,
            ]);
            $operation->lines()->delete();
            $this->applyMovement($operation, $payload);

            return $operation->fresh(['depot', 'employee', 'lines.article']);
        });
    }

    public function delete(Operation $operation): void
    {
        DB::transaction(function () use ($operation): void {
            $this->reverseMovement($operation);
            $operation->delete();
        });
    }

    private function applyMovement(Operation $operation, array $payload): void
    {
        $depot = Depot::query()->with('articles')->findOrFail($payload['depot_id']);
        $stockByArticle = $depot->articles->mapWithKeys(fn ($article) => [$article->id => (int) $article->pivot->quantity])->all();

        foreach ($payload['lines'] as $line) {
            $article = Article::findOrFail($line['article_id']);
            $currentQuantity = $stockByArticle[$article->id] ?? 0;

            if ($payload['type'] === 'sortie' && $currentQuantity < $line['quantity']) {
                throw ValidationException::withMessages(['lines' => "Stock insuffisant pour {$article->reference}."]);
            }

            $newQuantity = $payload['type'] === 'entree'
                ? $currentQuantity + $line['quantity']
                : $currentQuantity - $line['quantity'];

            $depot->articles()->syncWithoutDetaching([$article->id => ['quantity' => $newQuantity]]);
            $stockByArticle[$article->id] = $newQuantity;

            $operation->lines()->create([
                'article_id' => $article->id,
                'quantity' => $line['quantity'],
                'reference' => $article->reference,
            ]);
        }
    }

    private function reverseMovement(Operation $operation): void
    {
        $operation->loadMissing('lines');
        $depot = Depot::query()->with('articles')->findOrFail($operation->depot_id);
        $stockByArticle = $depot->articles->mapWithKeys(fn ($article) => [$article->id => (int) $article->pivot->quantity])->all();

        foreach ($operation->lines->groupBy('article_id') as $articleId => $lines) {
            $quantity = (int) $lines->sum('quantity');
            $currentQuantity = $stockByArticle[$articleId] ?? 0;
            $article = Article::findOrFail($articleId);

            if ($operation->type === 'entree' && $currentQuantity < $quantity) {
                throw ValidationException::withMessages([
                    'operation' => "Impossible d’annuler l’opération : le stock {$article->reference} a déjà été consommé.",
                ]);
            }

            $newQuantity = $operation->type === 'entree'
                ? $currentQuantity - $quantity
                : $currentQuantity + $quantity;

            $depot->articles()->syncWithoutDetaching([$articleId => ['quantity' => $newQuantity]]);
            $stockByArticle[$articleId] = $newQuantity;
        }
    }

    private function baseQuery(array $filters)
    {
        return Operation::query()
            ->with(['depot', 'employee', 'lines'])
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where('reference', 'like', "%{$value}%"))
            ->when($filters['type'] ?? null, fn ($query, $value) => $query->where('type', $value))
            ->when($filters['depot_id'] ?? null, fn ($query, $value) => $query->where('depot_id', $value))
            ->when($filters['employee_id'] ?? null, fn ($query, $value) => $query->where('employee_id', $value))
            ->when($filters['date_from'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '<=', $value));
    }
}
