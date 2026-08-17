<?php

namespace App\Services;

use App\Models\Employee;
use App\Support\ExcelExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest()
            ->paginate(100)
            ->withQueryString()
            ->through(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'prenom' => $employee->prenom,
                'poste' => $employee->poste,
                'telephone' => $employee->telephone,
                'salary' => (float) $employee->salary,
                'salary_payment_day' => $employee->salary_payment_day,
                'status' => $employee->status,
            ]);
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)
            ->latest()
            ->get()
            ->map(fn (Employee $employee) => [
                $employee->name,
                $employee->prenom,
                $employee->poste,
                $employee->telephone,
                $employee->salary,
                $employee->salary_payment_day,
            ]);

        return ExcelExport::download('employes-export', ['Nom', 'Prenom', 'Poste', 'Telephone', 'Salaire DH', 'Jour paiement'], $rows);
    }

    private function baseQuery(array $filters)
    {
        return Employee::query()
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$value}%")
                ->orWhere('prenom', 'like', "%{$value}%")
                ->orWhere('telephone', 'like', "%{$value}%")))
            ->when($filters['poste'] ?? null, fn ($query, $value) => $query->where('poste', 'like', "%{$value}%"));
    }
}
