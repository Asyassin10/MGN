<?php

namespace App\Http\Controllers;

use App\Http\Requests\PinLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(PinLoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $destination = $user->isAdmin() ? route('dashboard') : match (true) {
            $user->canAccess('depots') => route('depots.index'),
            $user->canAccess('fournisseurs') => route('fournisseurs.index'),
            $user->canAccess('clients') => route('clients.index'),
            $user->canAccess('employees') => route('employees.index'),
            $user->canAccess('dashboard') => route('dashboard'),
            default => route('dashboard'),
        };

        return redirect()->to($destination);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
