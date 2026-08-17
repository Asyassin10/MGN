<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePinRequest;
use App\Models\Bank;
use App\Support\DeleteBlockers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('bank_search');

        return Inertia::render('Settings/Index', ['banks' => Bank::query()->withCount(['chequeClients', 'chequeFournisseurs'])->when($filters['bank_search'] ?? null, fn ($query, $value) => $query->where('name', 'like', "%{$value}%"))->orderBy('name')->paginate(100)->withQueryString(), 'filters' => $filters]);
    }

    public function updatePin(UpdatePinRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! Hash::check($request->string('current_pin')->toString(), (string) $user->pin)) {
            return back()->withErrors(['current_pin' => 'PIN actuel incorrect.']);
        }

        $user->forceFill([
            'pin' => $request->string('pin')->toString(),
        ])->save();

        return back()->with('success', 'PIN mis à jour.');
    }

    public function storeBank(Request $request): RedirectResponse
    {
        Bank::create($request->validate(['name' => ['required', 'string', 'max:255', 'unique:banks,name']]));

        return back()->with('success', 'Banque créée.');
    }

    public function updateBank(Request $request, Bank $bank): RedirectResponse
    {
        $bank->update($request->validate(['name' => ['required', 'string', 'max:255', 'unique:banks,name,'.$bank->id]]));

        return back()->with('success', 'Banque mise à jour.');
    }

    public function destroyBank(Bank $bank): RedirectResponse
    {
        $message = DeleteBlockers::message('cette banque', ['chèques clients' => $bank->chequeClients()->count(), 'chèques fournisseurs' => $bank->chequeFournisseurs()->count()]);
        if ($message) {
            return back()->with('error', $message);
        } $bank->delete();

        return back()->with('success', 'Banque supprimée.');
    }
}
