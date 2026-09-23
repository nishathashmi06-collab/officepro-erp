@extends('layouts.app')
@php($editing = $employee->exists)
@section('title', $editing ? 'Edit '.$employee->full_name : 'Add employee')

@section('content')
<x-page-header :title="$editing ? 'Edit employee' : 'Add employee'" :subtitle="$editing ? $employee->full_name.' · '.$employee->employee_code : 'Create a new employee record and, optionally, a login account.'"
    :breadcrumbs="['Employees' => route('employees.index')] + ($editing ? [$employee->full_name => route('employees.show', $employee), 'Edit' => null] : ['New employee' => null])" />

<form method="POST" action="{{ $editing ? route('employees.update', $employee) : route('employees.store') }}" enctype="multipart/form-data" novalidate>
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <x-card class="op-animate">
                <div class="op-form-section">Personal information</div>
                <div class="row">
                    <x-form.input class="col-md-6" name="first_name" label="First name" :value="$employee->first_name" required maxlength="80" />
                    <x-form.input class="col-md-6" name="last_name" label="Last name" :value="$employee->last_name" required maxlength="80" />
                    <x-form.input class="col-md-6" name="email" type="email" label="Email" :value="$employee->email" required />
                    <x-form.input class="col-md-6" name="phone" label="Phone" :value="$employee->phone" placeholder="+1 555 0100" />
                    <x-form.select class="col-md-6" name="gender" label="Gender" :options="collect(App\Models\Employee::GENDERS)->mapWithKeys(fn ($g) => [$g => label($g)])" :value="$employee->gender" placeholder="Select…" />
                    <x-form.input class="col-md-6" name="date_of_birth" type="date" label="Date of birth" :value="$employee->date_of_birth?->toDateString()" max="{{ now()->subYears(15)->toDateString() }}" />
                    <x-form.textarea class="col-12" name="address" label="Address" :value="$employee->address" rows="2" />
                    <x-form.input class="col-md-6" name="emergency_contact_name" label="Emergency contact name" :value="$employee->emergency_contact_name" />
                    <x-form.input class="col-md-6" name="emergency_contact_phone" label="Emergency contact phone" :value="$employee->emergency_contact_phone" />
                </div>

                <div class="op-form-section">Employment</div>
                <div class="row">
                    <x-form.input class="col-md-4" name="employee_code" label="Employee ID" :value="$employee->employee_code" help="Leave blank to auto-generate." />
                    <x-form.select class="col-md-4" name="department_id" label="Department" :options="$departments->pluck('name', 'id')" :value="$employee->department_id" placeholder="— None —" />
                    <x-form.select class="col-md-4" name="designation_id" label="Designation" :options="$designations->pluck('name', 'id')" :value="$employee->designation_id" placeholder="— None —" />
                    <x-form.input class="col-md-4" name="joining_date" type="date" label="Joining date" :value="$employee->joining_date?->toDateString()" required />
                    <x-form.select class="col-md-4" name="employment_type" label="Employment type" :options="collect(App\Models\Employee::EMPLOYMENT_TYPES)->mapWithKeys(fn ($t) => [$t => label($t)])" :value="$employee->employment_type" required />
                    <x-form.select class="col-md-4" name="status" label="Status" :options="collect(App\Models\Employee::STATUSES)->mapWithKeys(fn ($t) => [$t => label($t)])" :value="$employee->status" required />
                    <x-form.input class="col-md-6" name="salary" type="number" step="0.01" min="0" label="Monthly basic salary" :value="$employee->salary" :prefix="setting('currency_symbol')" required />
                    <x-form.textarea class="col-12" name="notes" label="Internal notes" :value="$employee->notes" rows="2" />
                </div>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Profile photo" class="mb-4 op-animate op-animate-1">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img id="photo-preview" src="{{ $employee->photo_url }}" class="rounded-circle {{ $employee->photo_url ? '' : 'd-none' }}" width="72" height="72" style="object-fit:cover" alt="">
                    @unless ($employee->photo_url)<x-avatar :name="$employee->full_name ?: 'New Employee'" size="72" />@endunless
                    <div class="small text-muted">JPG, PNG or WEBP, max 2 MB.</div>
                </div>
                <x-form.input name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" data-preview="#photo-preview" class="mb-2" />
                @if ($employee->profile_photo)
                    <x-form.checkbox name="remove_photo" label="Remove current photo" :switch="false" class="mb-0" />
                @endif
            </x-card>

            <x-card title="Login account" icon="bi-key" class="op-animate op-animate-2">
                @if ($editing && $employee->user)
                    <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded-3" style="background: var(--op-surface-2)">
                        <i class="bi bi-person-check text-success fs-5"></i>
                        <div class="small"><strong>{{ $employee->user->email }}</strong><br><span class="text-muted">{{ $employee->user->role?->name }} · {{ label($employee->user->status) }}</span></div>
                    </div>
                @endif
                <x-form.select name="user_id" label="Linked user account" :options="$availableUsers->mapWithKeys(fn ($u) => [$u->id => $u->name.' ('.$u->email.')'])" :value="$employee->user_id" placeholder="— Not linked —" help="Link an existing account so this person can sign in." />
                @if (! $editing || ! $employee->user_id)
                    <x-form.checkbox name="create_account" label="Create a new login account" data-toggle-target="#new-account" />
                    <div id="new-account" class="d-none">
                        <x-form.select name="account_role_id" label="Role" :options="$roles->pluck('name', 'id')" :value="$roles->firstWhere('slug', 'employee')?->id" required />
                        <x-form.input name="account_password" type="password" label="Initial password" autocomplete="new-password" help="Minimum 8 characters. The login e-mail will be the employee's e-mail." />
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <div class="op-card mt-4 p-3 d-flex justify-content-end gap-2 op-animate op-animate-3">
        <a href="{{ $editing ? route('employees.show', $employee) : route('employees.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i>{{ $editing ? 'Save changes' : 'Create employee' }}</button>
    </div>
</form>
@endsection
