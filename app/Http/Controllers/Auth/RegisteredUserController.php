<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        abort_unless(setting('registration_enabled') == '1', 404);

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(setting('registration_enabled') == '1', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Self-registered accounts always get the least-privileged role.
        $user = User::create([
            ...$data,
            'role_id' => Role::where('slug', Permissions::ROLE_EMPLOYEE)->value('id'),
            'status' => 'active',
        ]);

        event(new Registered($user));
        activity('register', 'auth', $user->name.' registered an account', $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to OfficePro! HR will link your account to your employee profile.');
    }
}
