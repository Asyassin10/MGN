<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePinRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index');
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
}
