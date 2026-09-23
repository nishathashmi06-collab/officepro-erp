<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with(['role', 'employee'])
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")))
            ->when($request->query('role'), fn ($q, $v) => $q->where('role_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('is_primary')->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', ['users' => $users, 'roles' => Role::orderBy('id')->get()]);
    }

    public function create(Request $request): View
    {
        return view('users.form', ['user' => new User(['status' => 'active']), 'roles' => $this->assignableRoles($request->user())]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('profile_photo');
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('photos', 'public');
        }

        $user = User::create($data);
        activity('created', 'users', "{$request->user()->name} created user {$user->name} ({$user->role->name})", $user);

        return redirect()->route('users.show', $user)->with('success', 'User account created.');
    }

    public function show(User $user): View
    {
        $user->load(['role', 'employee.department']);

        return view('users.show', [
            'account' => $user,
            'activity' => ActivityLog::where('user_id', $user->id)->latest('created_at')->latest('id')->limit(15)->get(),
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        return view('users.form', ['user' => $user, 'roles' => $this->assignableRoles($request->user())]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except(['profile_photo', 'password']);
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('photos', 'public');
        }

        $oldRole = $user->role?->name;
        $user->update($data);
        $user->load('role');

        $description = "{$request->user()->name} updated user {$user->name}";
        if ($oldRole !== $user->role->name) {
            $description .= " (role {$oldRole} → {$user->role->name})";
        }
        activity('updated', 'users', $description, $user);

        return redirect()->route('users.show', $user)->with('success', 'User updated.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('toggleStatus', $user);

        $user->update(['status' => $user->status === 'active' ? 'disabled' : 'active']);
        if ($user->status === 'disabled') {
            // End any active sessions of the disabled user.
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        activity($user->status === 'active' ? 'enabled' : 'disabled', 'users', "{$request->user()->name} ".($user->status === 'active' ? 'enabled' : 'disabled')." user {$user->name}", $user);

        return back()->with('success', 'User '.($user->status === 'active' ? 'enabled.' : 'disabled.'));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $data = $request->validate(['new_password' => ['required', 'confirmed', Password::defaults()]]);

        $user->update(['password' => $data['new_password']]);
        activity('password_reset', 'users', "{$request->user()->name} reset the password of {$user->name}", $user);

        return back()->with('success', "Password for {$user->name} has been reset.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is_primary) {
            return back()->with('error', 'The primary super admin account cannot be deleted.');
        }
        $this->authorize('delete', $user);

        $user->employee?->update(['user_id' => null]);
        $user->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        activity('deleted', 'users', "{$request->user()->name} deleted user {$user->name}", $user);

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }

    private function assignableRoles(User $actor)
    {
        return Role::orderBy('id')
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereNotIn('slug', ['super_admin', 'admin']))
            ->get();
    }
}
