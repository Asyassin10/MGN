<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Users/Index', ['users' => User::query()->latest()->get()->map(fn (User $user) => $this->serialize($user))]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request));

        return back()->with('success', 'Utilisateur créé.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, false);
        if (blank($data['pin'] ?? null)) {
            unset($data['pin']);
        }
        $user->update($data);

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 422, 'Vous ne pouvez pas supprimer votre propre compte.');
        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }

    private function validated(Request $request, bool $creating = true): array
    {
        $modules = collect(['dashboard', 'depots', 'fournisseurs', 'clients', 'employees'])->filter(fn (string $module) => $request->boolean('module_'.$module))->values()->all();
        $deleteModules = collect(['depots', 'fournisseurs', 'clients', 'employees'])->filter(fn (string $module) => $request->boolean('delete_'.$module))->values()->all();
        $request->merge(['modules' => $modules, 'delete_modules' => $deleteModules]);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:admin,restricted'], 'modules' => ['nullable', 'array'], 'modules.*' => ['in:dashboard,depots,fournisseurs,clients,employees'],
            'delete_modules' => ['nullable', 'array'], 'delete_modules.*' => ['in:depots,fournisseurs,clients,employees'],
            'pin' => [$creating ? 'required' : 'nullable', 'digits:6'],
        ];
        $data = $request->validate($rules);
        $data['permissions'] = ['modules' => $data['modules'] ?? [], 'delete' => $data['delete_modules'] ?? []];
        unset($data['modules'], $data['delete_modules']);
        if ($creating) {
            $data['email'] = Str::slug($data['name']).'-'.Str::lower(Str::random(8)).'@local';
            $data['password'] = Str::random(32);
        }

        return $data;
    }

    private function serialize(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'modules' => $user->permissions['modules'] ?? [], 'delete_modules' => $user->permissions['delete'] ?? []];
    }
}
