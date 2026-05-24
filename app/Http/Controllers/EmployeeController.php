<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Support\DeleteBlockers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function index(Request $request, EmployeeService $service): Response|StreamedResponse
    {
        $filters = $request->only(['search', 'poste']);

        if ($request->boolean('export')) {
            return $service->export($filters);
        }

        return Inertia::render('Employees/Index', [
            'employees' => $service->list($filters),
            'filters' => $filters,
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::create($request->validated());

        return back()->with('success', 'Employé créé.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return back()->with('success', 'Employé mis à jour.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $message = DeleteBlockers::message('cet employé', [
            'opérations' => $employee->operations()->count(),
        ]);

        if ($message) {
            return back()->with('error', $message);
        }

        $employee->delete();

        return back()->with('success', 'Employé supprimé.');
    }
}
