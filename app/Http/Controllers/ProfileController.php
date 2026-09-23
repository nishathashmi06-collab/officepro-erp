<?php

namespace App\Http\Controllers;

use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        return $user->employee
            ? redirect()->route('employees.show', $user->employee)
            : redirect()->route('profile.edit');
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('role', 'employee'),
            'notificationTypes' => AppNotification::types(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('photos', 'public');
        }

        $user->update($data);
        activity('updated', 'users', "{$user->name} updated their profile", $user);

        return back()->with('success', 'Profile updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ]);

        $request->user()->update(['password' => $request->input('password')]);
        activity('password_changed', 'users', "{$request->user()->name} changed their password", $request->user());

        return back()->with('success', 'Password changed successfully.');
    }

    /** Appearance + notification preferences. Also used via fetch() by the theme toggle. */
    public function preferences(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['nullable', Rule::in(['light', 'dark'])],
            'sidebar' => ['nullable', Rule::in(['expanded', 'collapsed'])],
            'email_notifications' => ['nullable', 'array'],
            'email_notifications.*' => ['boolean'],
            'section' => ['nullable', 'in:appearance,notifications'],
        ]);

        $user = $request->user();
        $prefs = $user->preferences ?? [];

        foreach (['theme', 'sidebar'] as $key) {
            if (! empty($data[$key])) {
                $prefs[$key] = $data[$key];
            }
        }

        if (($data['section'] ?? null) === 'notifications') {
            $prefs['email_notifications'] = collect(AppNotification::types())
                ->mapWithKeys(fn ($label, $key) => [$key => (bool) ($data['email_notifications'][$key] ?? false)])
                ->all();
        }

        $user->forceFill(['preferences' => $prefs])->save();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'Preferences saved.');
    }
}
