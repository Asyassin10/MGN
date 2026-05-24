<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Cheque;
use App\Support\DeleteBlockers;
use App\Support\ExcelExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankController extends Controller
{
    public function index(Request $request): Response|StreamedResponse
    {
        $filters = $request->only(['search']);
        $query = Bank::query()
            ->withCount(['chequeClients', 'chequeFournisseurs'])
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where('name', 'like', "%{$value}%"));

        if ($request->boolean('export')) {
            return ExcelExport::download('banques-export', ['Banque', 'Cheques clients', 'Cheques fournisseurs'], $query->latest()->get()->map(fn (Bank $bank) => [
                $bank->name,
                $bank->cheque_clients_count,
                $bank->cheque_fournisseurs_count,
            ]));
        }

        return Inertia::render('Banks/Index', [
            'banks' => $query
                ->latest()
                ->paginate(100)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Bank::create($request->validate(['name' => ['required', 'string', 'max:255', 'unique:banks,name']]));

        return back()->with('success', 'Banque créée.');
    }

    public function update(Request $request, Bank $bank): RedirectResponse
    {
        $bank->update($request->validate(['name' => ['required', 'string', 'max:255', 'unique:banks,name,'.$bank->id]]));

        return back()->with('success', 'Banque mise à jour.');
    }

    public function destroy(Bank $bank): RedirectResponse
    {
        $message = DeleteBlockers::message('cette banque', [
            'chèques clients' => $bank->chequeClients()->count(),
            'chèques fournisseurs' => $bank->chequeFournisseurs()->count(),
            'chèques' => Cheque::query()->where('banque', $bank->name)->count(),
        ]);

        if ($message) {
            return back()->with('error', $message);
        }

        $bank->delete();

        return back()->with('success', 'Banque supprimée.');
    }
}
