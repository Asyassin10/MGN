<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\EmployeeSalaryPayment;
use App\Services\EmployeeService;
use App\Support\DeleteBlockers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function index(Request $request, EmployeeService $service): Response|StreamedResponse
    {
        $filters = [...$request->only(['search', 'poste']), 'selected_ids' => $request->validate(['selected_ids' => ['nullable', 'array'], 'selected_ids.*' => ['integer', 'distinct']])['selected_ids'] ?? []];

        if ($request->boolean('export')) {
            return $service->export($filters);
        }

        return Inertia::render('Employees/Index', [
            'employees' => $service->list($filters),
            'filters' => $filters,
        ]);
    }

    public function show(Employee $employee): Response
    {
        $month = request('month', now()->format('Y-m'));
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $workDays = $employee->workDays()->whereBetween('work_date', [$start, $start->copy()->endOfMonth()])->get()->keyBy(fn ($row) => $row->work_date->format('Y-m-d'));
        $absences = $employee->absences()->whereBetween('absence_date', [$start, $start->copy()->endOfMonth()])->get()->keyBy(fn ($row) => $row->absence_date->format('Y-m-d'));
        $calendar = collect(range(1, $start->daysInMonth))->map(function (int $day) use ($start, $employee, $workDays, $absences) {
            $date = $start->copy()->day($day);
            $key = $date->toDateString();

            return ['date' => $key, 'day' => $day, 'status' => $absences->has($key) ? 'absent' : ($workDays->has($key) ? $workDays->get($key)->status : (in_array($date->dayOfWeekIso, $employee->work_days ?? [1, 2, 3, 4, 5], true) ? 'scheduled' : 'not_scheduled'))];
        });

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
            'month' => $month,
            'calendar' => $calendar,
            'absences' => $employee->absences()->latest('absence_date')->get()->map(fn ($absence) => ['id' => $absence->id, 'absence_date' => $absence->absence_date?->format('Y-m-d'), 'status' => $absence->status, 'note' => $absence->note]),
            'salaryPayments' => $employee->salaryPayments()->latest('payment_date')->get()->map(fn ($payment) => ['id' => $payment->id, 'month' => $payment->month, 'type' => $payment->type, 'payment_date' => $payment->payment_date?->format('Y-m-d'), 'amount' => (float) $payment->amount, 'status' => $payment->status, 'note' => $payment->note]),
        ]);
    }

    public function paymentHistory(Employee $employee): Response
    {
        $payments = $employee->salaryPayments()->latest('payment_date')->get()->map(fn (EmployeeSalaryPayment $payment) => ['id' => $payment->id, 'month' => $payment->month, 'type' => $payment->type, 'payment_date' => $payment->payment_date?->format('Y-m-d'), 'amount' => (float) $payment->amount, 'note' => $payment->note]);

        return Inertia::render('Employees/PaymentHistory', [
            'employee' => ['id' => $employee->id, 'name' => $employee->name],
            'payments' => $payments,
            'summary' => ['count' => $payments->count(), 'total' => $payments->sum('amount'), 'salary' => $payments->where('type', 'salary')->sum('amount'), 'advance' => $payments->where('type', 'advance')->sum('amount'), 'bonus' => $payments->where('type', 'bonus')->sum('amount')],
        ]);
    }

    public function absenceHistory(Employee $employee): Response
    {
        $absences = $employee->absences()->latest('absence_date')->get()->map(fn ($absence) => ['id' => $absence->id, 'absence_date' => $absence->absence_date?->format('Y-m-d'), 'status' => $absence->status, 'note' => $absence->note]);

        return Inertia::render('Employees/AbsenceHistory', ['employee' => ['id' => $employee->id, 'name' => $employee->name], 'absences' => $absences, 'count' => $absences->count()]);
    }

    public function storeWorkDay(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate(['work_date' => ['required', 'date'], 'status' => ['required', 'in:worked,off'], 'note' => ['nullable', 'string']]);
        $workDay = $employee->workDays()->whereDate('work_date', $data['work_date'])->first();
        $workDay ? $workDay->update($data) : $employee->workDays()->create($data);

        return back()->with('success', 'Journée travaillée enregistrée.');
    }

    public function storeAbsence(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate(['absence_date' => ['required', 'date'], 'status' => ['required', 'in:justified,unjustified,leave'], 'note' => ['nullable', 'string']]);
        $absence = $employee->absences()->whereDate('absence_date', $data['absence_date'])->first();
        $absence ? $absence->update($data) : $employee->absences()->create($data);

        return back()->with('success', 'Absence enregistrée.');
    }

    public function storeSalaryPayment(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate(['month' => ['required', 'date_format:Y-m'], 'payment_date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'min:0'], 'type' => ['required', 'in:salary,advance,bonus'], 'note' => ['nullable', 'string']]);
        $employee->salaryPayments()->updateOrCreate(['month' => $data['month'], 'type' => $data['type']], [...$data, 'status' => 'paid']);

        return back()->with('success', 'Paiement employé enregistré.');
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
