<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PinLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return ['pin' => ['required', 'digits:6']];
    }

    public function authenticate(): void
    {
        $this->validateResolved();

        $user = User::query()->whereNotNull('pin')->first();

        if (! $user || ! Hash::check($this->string('pin')->toString(), $user->pin)) {
            throw ValidationException::withMessages(['pin' => 'PIN incorrect.']);
        }

        Auth::login($user);
    }
}
