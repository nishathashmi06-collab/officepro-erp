<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const DATE_FORMATS = ['M d, Y', 'd/m/Y', 'm/d/Y', 'Y-m-d', 'd M Y'];

    public function edit(): View
    {
        return view('settings.edit', [
            'settings' => Settings::all(),
            'timezones' => timezone_identifiers_list(),
            'dateFormats' => collect(self::DATE_FORMATS)->mapWithKeys(fn ($f) => [$f => now()->format($f)])->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_email' => ['nullable', 'email', 'max:190'],
            'company_phone' => ['nullable', 'string', 'max:40'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_website' => ['nullable', 'url', 'max:190'],
            'company_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            'timezone' => ['required', 'timezone'],
            'date_format' => ['required', Rule::in(self::DATE_FORMATS)],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'work_start_time' => ['required', 'date_format:H:i'],
            'work_end_time' => ['required', 'date_format:H:i', 'after:work_start_time'],
            'late_grace_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'default_working_hours' => ['required', 'numeric', 'min:1', 'max:24'],
            'document_expiry_days' => ['required', 'integer', 'min:1', 'max:365'],
            'registration_enabled' => ['nullable', 'boolean'],
        ]);

        $values = collect($data)->except(['company_logo', 'remove_logo'])->all();
        $values['currency'] = strtoupper($values['currency']);
        $values['registration_enabled'] = $request->boolean('registration_enabled') ? '1' : '0';

        if ($request->hasFile('company_logo')) {
            if ($old = Settings::get('company_logo')) {
                Storage::disk('public')->delete($old);
            }
            $values['company_logo'] = $request->file('company_logo')->store('branding', 'public');
        } elseif ($request->boolean('remove_logo') && ($old = Settings::get('company_logo'))) {
            Storage::disk('public')->delete($old);
            $values['company_logo'] = null;
        }

        Settings::set($values);
        activity('updated', 'settings', "{$request->user()->name} updated system settings");

        return back()->with('success', 'Settings saved.');
    }
}
