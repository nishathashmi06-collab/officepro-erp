@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<x-page-header title="Settings" subtitle="Company profile, regional preferences and working rules." :breadcrumbs="['Settings' => null]">
    <x-slot:actions><a href="{{ route('profile.edit') }}" class="btn btn-light"><i class="bi bi-person-gear"></i> My preferences</a></x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" novalidate>
    @csrf @method('PUT')
    <div class="row g-4">
        <div class="col-xl-8">
            <x-card title="Company" icon="bi-building" class="mb-4">
                <div class="row">
                    <x-form.input class="col-md-6" name="company_name" label="Company name" :value="$settings['company_name']" required />
                    <x-form.input class="col-md-6" name="company_email" type="email" label="Email" :value="$settings['company_email']" />
                    <x-form.input class="col-md-6" name="company_phone" label="Phone" :value="$settings['company_phone']" />
                    <x-form.input class="col-md-6" name="company_website" type="url" label="Website" :value="$settings['company_website']" placeholder="https://" />
                    <x-form.textarea class="col-12" name="company_address" label="Address" :value="$settings['company_address']" rows="2" help="Printed on salary slips and reports." />
                </div>
            </x-card>
            <x-card title="System" icon="bi-globe2" class="mb-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="f_timezone">Timezone<span class="req">*</span></label>
                        <select name="timezone" id="f_timezone" class="form-select @error('timezone') is-invalid @enderror">
                            @foreach ($timezones as $tz)<option value="{{ $tz }}" @selected(old('timezone', $settings['timezone']) === $tz)>{{ $tz }}</option>@endforeach
                        </select>
                        @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <x-form.select class="col-md-6" name="date_format" label="Date format" :options="$dateFormats" :value="$settings['date_format']" required />
                    <x-form.input class="col-md-6" name="currency" label="Currency code" :value="$settings['currency']" maxlength="3" required help="ISO code, e.g. USD, EUR, GBP" />
                    <x-form.input class="col-md-6" name="currency_symbol" label="Currency symbol" :value="$settings['currency_symbol']" maxlength="5" required />
                </div>
            </x-card>
            <x-card title="Working hours & rules" icon="bi-clock" class="mb-4">
                <div class="row">
                    <x-form.input class="col-md-4" name="work_start_time" type="time" label="Workday starts" :value="$settings['work_start_time']" required />
                    <x-form.input class="col-md-4" name="work_end_time" type="time" label="Workday ends" :value="$settings['work_end_time']" required />
                    <x-form.input class="col-md-4" name="late_grace_minutes" type="number" min="0" max="180" label="Late after (grace)" :value="$settings['late_grace_minutes']" suffix="min" required />
                    <x-form.input class="col-md-6" name="default_working_hours" type="number" step="0.5" min="1" max="24" label="Default working hours / day" :value="$settings['default_working_hours']" suffix="hours" required help="Check-outs under half of this are recorded as half day." />
                    <x-form.input class="col-md-6" name="document_expiry_days" type="number" min="1" max="365" label="Document expiry warning" :value="$settings['document_expiry_days']" suffix="days before" required />
                </div>
                <x-form.checkbox name="registration_enabled" label="Allow public self-registration (new accounts get the Employee role)" :checked="$settings['registration_enabled'] == '1'" />
            </x-card>
        </div>
        <div class="col-xl-4">
            <x-card title="Logo" icon="bi-image" class="mb-4">
                <div class="text-center mb-3">
                    <img id="logo-preview" src="{{ $settings['company_logo'] ? Storage::disk('public')->url($settings['company_logo']) : '' }}" class="rounded-3 border {{ $settings['company_logo'] ? '' : 'd-none' }}" style="max-height:90px;max-width:100%" alt="Company logo">
                    @unless ($settings['company_logo'])<div class="op-brand-logo mx-auto" style="width:64px;height:64px;font-size:1.6rem"><i class="bi bi-briefcase-fill"></i></div>@endunless
                </div>
                <x-form.input name="company_logo" type="file" accept="image/png,image/jpeg,image/webp" data-preview="#logo-preview" help="Square PNG/JPG/WEBP, max 1 MB." />
                @if ($settings['company_logo'])<x-form.checkbox name="remove_logo" label="Remove logo" :switch="false" />@endif
            </x-card>
            <x-card title="Appearance & notifications" icon="bi-palette" class="mb-4">
                <p class="small text-muted">Theme (light/dark), sidebar state and e-mail notification preferences are saved per user.</p>
                <a href="{{ route('profile.edit') }}#appearance" class="btn btn-light w-100"><i class="bi bi-sliders"></i> Open my preferences</a>
            </x-card>
            <div class="op-card p-3 position-sticky" style="top: 90px"><button class="btn btn-primary w-100 btn-lg"><i class="bi bi-check2"></i> Save settings</button></div>
        </div>
    </div>
</form>
@endsection
