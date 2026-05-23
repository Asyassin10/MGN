<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOperationRequest;
use App\Models\Article;
use App\Models\Depot;
use App\Models\Employee;
use App\Models\Operation;
use App\Services\OperationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationController extends Controller
{
    public function index(Request $request, OperationService $service): Response|StreamedResponse
    {
        $filters = $request->only(['search', 'type', 'depot_id', 'employee_id', 'date_from', 'date_to']);

        if ($request->boolean('export')) {
            return $service->export($filters);
        }

        $options = $service->filterOptions();

        return Inertia::render('Operations/Index', [
            'operations' => $service->list($filters),
            'filters' => $filters,
            'depots' => $options['depots'],
            'employees' => $options['employees'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Operations/Create', [
            'depots' => Depot::query()->orderBy('name')->get(['id', 'name'])->map(fn ($depot) => ['value' => (string) $depot->id, 'label' => $depot->name]),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn ($employee) => ['value' => (string) $employee->id, 'label' => $employee->name]),
            'articles' => Article::query()->orderBy('name')->get(['id', 'reference', 'name'])->map(fn ($article) => ['value' => (string) $article->id, 'label' => "{$article->reference} - {$article->display_name}"]),
        ]);
    }

    public function edit(Operation $operation, OperationService $service): Response
    {
        return Inertia::render('Operations/Edit', [
            'operation' => $service->form($operation),
            'depots' => Depot::query()->orderBy('name')->get(['id', 'name'])->map(fn ($depot) => ['value' => (string) $depot->id, 'label' => $depot->name]),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn ($employee) => ['value' => (string) $employee->id, 'label' => $employee->name]),
            'articles' => Article::query()->orderBy('name')->get(['id', 'reference', 'name'])->map(fn ($article) => ['value' => (string) $article->id, 'label' => "{$article->reference} - {$article->display_name}"]),
        ]);
    }

    public function show(Request $request, Operation $operation, OperationService $service): Response|StreamedResponse
    {
        if ($request->boolean('export')) {
            return $service->exportLines($operation);
        }

        return Inertia::render('Operations/Show', [
            'operation' => $service->show($operation),
        ]);
    }

    public function pdf(Operation $operation, OperationService $service): HttpResponse
    {
        return $service->pdf($operation);
    }

    public function store(StoreOperationRequest $request, OperationService $service): RedirectResponse
    {
        $operation = $service->create($request->validated());

        return redirect()->route('operations.show', $operation)->with('success', 'Opération enregistrée.');
    }

    public function update(StoreOperationRequest $request, Operation $operation, OperationService $service): RedirectResponse
    {
        $service->update($operation, $request->validated());

        return redirect()->route('operations.show', $operation)->with('success', 'Opération mise à jour.');
    }

    public function destroy(Operation $operation, OperationService $service): RedirectResponse
    {
        try {
            $service->delete($operation);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first('operation'));
        }

        return redirect()->route('operations.index')->with('success', 'Opération supprimée et stock rétabli.');
    }
}
