<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Notifications\AssetAssigned;
use App\Notifications\DocumentExpiring;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveReviewed;
use App\Notifications\PayrollGenerated;
use App\Notifications\TaskAssigned;
use App\Support\Permissions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * ==========================================================================
 *  DEMO / SEED DATA — every person, company and record created here is
 *  fictional and exists only to demonstrate OfficePro. All demo accounts use
 *  the password "password". Do NOT run this seeder in production.
 * ==========================================================================
 */
class DemoDataSeeder extends Seeder
{
    private const DEMO_NOTE = 'DEMO DATA — created by DemoDataSeeder.';

    private array $users = [];

    /** @var array<string, Employee> */
    private array $employees = [];

    public function run(): void
    {
        mt_srand(2026);

        $departments = $this->departments();
        $this->people($departments);
        $this->assignManagers($departments);
        $this->leaves();
        $this->attendance();
        $this->tasks();
        $this->payroll();
        $this->expenses();
        $this->assets();
        $this->documents();
        $this->activity();

        $this->command?->info('Demo data seeded. Log in with superadmin@officepro.test / password.');
    }

    private function departments(): array
    {
        $data = [
            'IT' => ['IT', 'Software development, infrastructure and IT support.'],
            'HR' => ['HR', 'Recruitment, people operations and employee wellbeing.'],
            'Finance' => ['FIN', 'Accounting, payroll processing and budgeting.'],
            'Marketing' => ['MKT', 'Brand, campaigns and digital marketing.'],
            'Sales' => ['SAL', 'New business and client account management.'],
            'Operations' => ['OPS', 'Facilities, logistics and day-to-day operations.'],
            'Administration' => ['ADM', 'Executive office and company administration.'],
        ];

        $out = [];
        foreach ($data as $name => [$code, $description]) {
            $out[$name] = Department::firstOrCreate(['name' => $name], ['code' => $code, 'description' => $description, 'status' => 'active']);
        }

        return $out;
    }

    private function people(array $departments): void
    {
        $roles = Role::pluck('id', 'slug');
        $designations = Designation::pluck('id', 'name');

        // key => [first, last, email, role, department, designation, salary, gender, dob, joined, type]
        $people = [
            'superadmin' => ['Olivia', 'Bennett', 'superadmin@officepro.test', Permissions::ROLE_SUPER_ADMIN, 'Administration', 'CEO', 12500, 'female', '1980-04-12', '2018-01-08', 'full_time'],
            'admin' => ['Marcus', 'Reid', 'admin@officepro.test', Permissions::ROLE_ADMIN, 'Administration', 'Office Administrator', 6800, 'male', '1987-09-30', '2019-03-18', 'full_time'],
            'hr' => ['Priya', 'Sharma', 'hr@officepro.test', Permissions::ROLE_HR, 'HR', 'HR Manager', 7200, 'female', '1988-'.today()->addDays(6)->format('m-d'), '2019-06-03', 'full_time'],
            'manager' => ['James', 'Carter', 'manager@officepro.test', Permissions::ROLE_MANAGER, 'IT', 'Manager', 8600, 'male', '1985-02-17', '2019-09-09', 'full_time'],
            'manager2' => ['Sofia', 'Martinez', 'manager2@officepro.test', Permissions::ROLE_MANAGER, 'Sales', 'Manager', 8200, 'female', '1986-11-05', '2020-01-13', 'full_time'],
            'employee' => ['Daniel', 'Brooks', 'employee@officepro.test', Permissions::ROLE_EMPLOYEE, 'IT', 'Developer', 5400, 'male', '1994-'.today()->addDays(12)->format('m-d'), '2021-04-05', 'full_time'],
            'e2' => ['Aisha', 'Khan', 'aisha.khan@officepro.test', Permissions::ROLE_EMPLOYEE, 'IT', 'Developer', 5200, 'female', '1995-07-21', '2022-02-14', 'full_time'],
            'e3' => ['Lucas', 'Weber', 'lucas.weber@officepro.test', Permissions::ROLE_EMPLOYEE, 'IT', 'Designer', 4600, 'male', '1996-03-02', '2022-08-01', 'full_time'],
            'e4' => ['Emma', 'Thompson', 'emma.thompson@officepro.test', Permissions::ROLE_EMPLOYEE, 'Sales', 'Sales Executive', 4200, 'female', '1993-12-11', '2021-10-18', 'full_time'],
            'e5' => ['Noah', 'Kim', 'noah.kim@officepro.test', Permissions::ROLE_EMPLOYEE, 'Sales', 'Sales Executive', 4100, 'male', '1997-'.today()->addDays(20)->format('m-d'), '2023-01-09', 'full_time'],
            'e6' => ['Chloe', 'Dubois', 'chloe.dubois@officepro.test', Permissions::ROLE_EMPLOYEE, 'Marketing', 'Marketing Officer', 4500, 'female', '1992-05-27', '2022-05-16', 'full_time'],
            'e7' => ['Ethan', 'Walker', 'ethan.walker@officepro.test', Permissions::ROLE_EMPLOYEE, 'Finance', 'Accountant', 5000, 'male', '1990-08-08', '2020-07-06', 'full_time'],
            'e8' => ['Grace', 'Okafor', 'grace.okafor@officepro.test', Permissions::ROLE_EMPLOYEE, 'Finance', 'Accountant', 4800, 'female', '1991-01-19', '2021-03-22', 'part_time'],
            'e9' => ['Mateo', 'Rossi', 'mateo.rossi@officepro.test', Permissions::ROLE_EMPLOYEE, 'Operations', 'Office Assistant', 3200, 'male', '1999-10-03', '2024-02-05', 'contract'],
            'e10' => ['Hannah', 'Schmidt', 'hannah.schmidt@officepro.test', Permissions::ROLE_EMPLOYEE, 'HR', 'HR Officer', 3900, 'female', '2000-06-14', '2025-06-02', 'intern'],
        ];

        $code = 1001;
        foreach ($people as $key => [$first, $last, $email, $role, $dept, $designation, $salary, $gender, $dob, $joined, $type]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => "$first $last",
                'password' => 'password',
                'role_id' => $roles[$role],
                'status' => 'active',
                'is_primary' => $key === 'superadmin',
                'phone' => '+1 555 01'.str_pad((string) ($code - 1000), 2, '0', STR_PAD_LEFT),
                'email_verified_at' => now(),
                'last_login_at' => now()->subHours(mt_rand(1, 72)),
            ]);
            $this->users[$key] = $user;

            $this->employees[$key] = Employee::firstOrCreate(['email' => $email], [
                'user_id' => $user->id,
                'employee_code' => 'EMP-'.$code++,
                'first_name' => $first,
                'last_name' => $last,
                'phone' => $user->phone,
                'gender' => $gender,
                'date_of_birth' => $dob,
                'address' => mt_rand(10, 999).' '.['Maple Street', 'Oak Avenue', 'Cedar Lane', 'Harbor Road', 'Park Boulevard'][mt_rand(0, 4)].', Metro City',
                'emergency_contact_name' => ['Alex', 'Jordan', 'Taylor', 'Morgan', 'Casey'][mt_rand(0, 4)].' '.$last,
                'emergency_contact_phone' => '+1 555 09'.mt_rand(10, 99),
                'department_id' => $departments[$dept]->id,
                'designation_id' => $designations[$designation] ?? null,
                'joining_date' => $joined,
                'employment_type' => $type,
                'salary' => $salary,
                'status' => 'active',
                'notes' => self::DEMO_NOTE,
            ]);
        }
    }

    private function assignManagers(array $departments): void
    {
        $map = ['IT' => 'manager', 'Sales' => 'manager2', 'Marketing' => 'manager2', 'HR' => 'hr', 'Administration' => 'superadmin', 'Operations' => 'admin', 'Finance' => 'hr'];
        foreach ($map as $dept => $key) {
            $departments[$dept]->update(['manager_id' => $this->employees[$key]->id]);
        }
    }

    private function leaves(): void
    {
        $types = LeaveType::pluck('id', 'code');
        $hr = $this->users['hr'];
        $today = today();

        $records = [
            // key, type, start offset, length (calendar days), status, reason
            ['e4', 'AL', -40, 4, 'approved', 'Family vacation.'],
            ['e7', 'SL', -25, 1, 'approved', 'Flu and fever — doctor advised rest.'],
            ['e2', 'CL', -12, 0, 'approved', 'Personal errands.'],
            ['e9', 'UL', -18, 1, 'approved', 'Extended trip back home.'],
            ['e6', 'AL', -1, 3, 'approved', 'Attending a wedding out of town.'], // covers today
            ['e3', 'SL', -8, 0, 'rejected', 'Dentist appointment.'],
            ['employee', 'AL', 14, 4, 'pending', 'Short holiday with family.'],
            ['e5', 'CL', 5, 0, 'pending', 'Moving to a new apartment.'],
            ['e10', 'EL', 2, 1, 'pending', 'Family emergency.'],
            ['e8', 'AL', 30, 6, 'pending', 'Annual trip.'],
            ['manager2', 'AL', -60, 4, 'approved', 'Summer break.'],
            ['e2', 'AL', 21, 2, 'cancelled', 'Conference (cancelled).'],
        ];

        foreach ($records as [$key, $code, $offset, $length, $status, $reason]) {
            $start = $today->copy()->addDays($offset);
            while ($start->isWeekend()) {
                $start->addDay();
            }
            $end = $start->copy()->addDays($length);
            while ($end->isWeekend()) {
                $end->addDay();
            }

            $leave = Leave::create([
                'employee_id' => $this->employees[$key]->id,
                'leave_type_id' => $types[$code],
                'start_date' => $start,
                'end_date' => $end,
                'days' => Leave::countDays($start->toDateString(), $end->toDateString()),
                'reason' => $reason,
                'status' => $status,
                'reviewed_by' => in_array($status, ['approved', 'rejected']) ? $hr->id : null,
                'reviewed_at' => in_array($status, ['approved', 'rejected']) ? $start->copy()->subDays(3) : null,
                'review_note' => $status === 'rejected' ? 'Please schedule outside the release week.' : null,
                'created_at' => $start->copy()->subDays(7)->min(now()->subHours(2)),
            ]);

            if ($status === 'pending') {
                $hr->notifyNow(new LeaveRequested($leave->load('employee', 'leaveType')));
                $manager = $leave->employee->department?->manager?->user;
                if ($manager && ! $manager->is($hr) && ! $manager->is($leave->employee->user)) {
                    $manager->notifyNow(new LeaveRequested($leave));
                }
            } elseif (in_array($status, ['approved', 'rejected'])) {
                $leave->employee->user?->notifyNow(new LeaveReviewed($leave->load('leaveType')));
            }
        }
    }

    private function attendance(): void
    {
        $start = today()->subDays(45);
        $settingsStart = '09:00';

        $leaveDays = Leave::where('status', 'approved')->get()
            ->flatMap(function (Leave $l) {
                $days = [];
                for ($d = $l->start_date->copy(); $d->lte($l->end_date); $d->addDay()) {
                    $days[] = $l->employee_id.'|'.$d->toDateString();
                }

                return $days;
            })->flip();

        $rows = [];
        foreach ($this->employees as $key => $employee) {
            for ($date = $start->copy(); $date->lte(today()); $date->addDay()) {
                if ($date->isWeekend() || $date->lt($employee->joining_date)) {
                    continue;
                }

                $isToday = $date->isToday();
                // Leave the main demo employee un-checked-in today so check-in can be tried.
                if ($isToday && in_array($key, ['employee', 'e9', 'e5'], true)) {
                    continue;
                }

                if (isset($leaveDays[$employee->id.'|'.$date->toDateString()])) {
                    $rows[] = $this->attendanceRow($employee->id, $date, null, null, 'leave');

                    continue;
                }

                $roll = mt_rand(1, 100);
                if ($roll <= 5 && ! $isToday) {
                    $rows[] = $this->attendanceRow($employee->id, $date, null, null, 'absent');

                    continue;
                }

                $late = $roll > 5 && $roll <= 17;
                $in = Carbon::parse($date->toDateString().' '.$settingsStart)->addMinutes($late ? mt_rand(18, 70) : mt_rand(-25, 12));
                $halfDay = $roll > 17 && $roll <= 21;
                $out = $isToday ? null : $in->copy()->addMinutes($halfDay ? mt_rand(180, 230) : mt_rand(480, 560));
                $hours = $out ? Attendance::calculateHours($in->format('H:i:s'), $out->format('H:i:s')) : 0;
                $status = $halfDay && ! $isToday ? 'half_day' : ($late ? 'late' : 'present');

                $rows[] = $this->attendanceRow($employee->id, $date, $in->format('H:i:s'), $out?->format('H:i:s'), $status, $hours);
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Attendance::insert($chunk);
        }
    }

    private function attendanceRow(int $employeeId, Carbon $date, ?string $in, ?string $out, string $status, float $hours = 0): array
    {
        return [
            'employee_id' => $employeeId,
            'date' => $date->toDateString(),
            'check_in' => $in,
            'check_out' => $out,
            'status' => $status,
            'working_hours' => $hours,
            'notes' => null,
            'created_at' => $date->copy()->setTime(9, 0),
            'updated_at' => $date->copy()->setTime(18, 0),
        ];
    }

    private function tasks(): void
    {
        $e = $this->employees;
        $u = $this->users;
        $today = today();

        $tasks = [
            // title, assignee, creator, priority, status, due offset, progress, description
            ['Migrate customer portal to new API', 'employee', 'manager', 'high', 'in_progress', 6, 55, 'Replace legacy endpoints with the v2 REST API and update integration tests.'],
            ['Fix login rate-limit bug', 'employee', 'manager', 'urgent', 'review', 1, 90, 'Users are locked out too early after a password reset.'],
            ['Write onboarding documentation', 'employee', 'manager', 'medium', 'pending', 12, 0, 'Developer onboarding guide for the new starters.'],
            ['Set up automated database backups', 'e2', 'manager', 'high', 'in_progress', 3, 40, 'Nightly backups with 30-day retention and restore test.'],
            ['Upgrade office Wi-Fi access points', 'e2', 'manager', 'medium', 'completed', -5, 100, 'Replace the three ageing access points on floor 2.'],
            ['Refresh company website landing page', 'e3', 'manager', 'medium', 'in_progress', 9, 35, 'New hero section and customer logos.'],
            ['Design Q4 campaign banners', 'e3', 'manager2', 'low', 'pending', 18, 0, 'Banners for social and display ads.'],
            ['Security patching – October cycle', 'e2', 'manager', 'urgent', 'pending', -2, 10, 'Apply OS and dependency security patches to production servers.'],
            ['Prepare Q3 sales pipeline review', 'e4', 'manager2', 'high', 'in_progress', 2, 70, 'Consolidate CRM data for the quarterly review meeting.'],
            ['Follow up with enterprise leads', 'e5', 'manager2', 'high', 'pending', 4, 0, 'Call back the 12 enterprise leads from the trade show.'],
            ['Update product price list', 'e4', 'manager2', 'medium', 'completed', -9, 100, 'Apply new pricing effective next month.'],
            ['Client proposal for Northwind Ltd.', 'e5', 'manager2', 'urgent', 'review', 0, 85, 'Final proposal including support tiers.'],
            ['Launch newsletter for September', 'e6', 'manager2', 'medium', 'completed', -14, 100, 'Monthly newsletter to 4,500 subscribers.'],
            ['Social media content calendar', 'e6', 'manager2', 'low', 'in_progress', 11, 45, 'Plan posts for the next 6 weeks.'],
            ['Month-end reconciliation', 'e7', 'admin', 'high', 'in_progress', 5, 60, 'Reconcile bank statements and petty cash.'],
            ['Vendor invoice audit', 'e8', 'admin', 'medium', 'pending', 15, 0, 'Audit Q3 vendor invoices for duplicates.'],
            ['Prepare annual budget draft', 'e7', 'superadmin', 'high', 'pending', 25, 5, 'First draft of next year\'s budget per department.'],
            ['Organise office supplies inventory', 'e9', 'admin', 'low', 'completed', -3, 100, 'Count stock and reorder low items.'],
            ['Arrange fire-safety drill', 'e9', 'admin', 'medium', 'pending', 8, 0, 'Coordinate with building management.'],
            ['Screen applicants for developer role', 'e10', 'hr', 'high', 'in_progress', 4, 50, 'Shortlist candidates for the backend developer vacancy.'],
            ['Update employee handbook', 'e10', 'hr', 'medium', 'pending', 20, 0, 'Reflect the new remote-work and leave policies.'],
            ['Quarterly performance review schedule', 'hr', 'superadmin', 'medium', 'in_progress', 10, 30, 'Publish review calendar for all departments.'],
            ['Retire legacy file server', 'manager', 'admin', 'medium', 'cancelled', -20, 20, 'Postponed until next fiscal year.'],
            ['Team offsite planning', 'manager2', 'superadmin', 'low', 'pending', 30, 0, 'Venue, agenda and budget for the sales offsite.'],
        ];

        foreach ($tasks as [$title, $assignee, $creator, $priority, $status, $due, $progress, $description]) {
            $created = $today->copy()->subDays(mt_rand(5, 25));
            $task = Task::create([
                'title' => $title,
                'description' => $description,
                'assigned_to' => $e[$assignee]->id,
                'created_by' => $u[$creator]->id,
                'department_id' => $e[$assignee]->department_id,
                'priority' => $priority,
                'status' => $status,
                'due_date' => $today->copy()->addDays($due),
                'progress' => $progress,
                'completed_at' => $status === 'completed' ? $today->copy()->addDays($due - 1) : null,
                'created_at' => $created,
                'updated_at' => $created->copy()->addDays(2),
            ]);

            ActivityLog::create(['user_id' => $u[$creator]->id, 'action' => 'created', 'module' => 'tasks', 'record_id' => $task->id, 'description' => "{$u[$creator]->name} assigned \"{$title}\" to {$e[$assignee]->full_name}", 'created_at' => $created]);

            if (in_array($status, ['in_progress', 'review', 'completed'])) {
                $task->comments()->create(['user_id' => $e[$assignee]->user_id, 'comment' => ['Started working on this.', 'Making good progress, will update soon.', 'Done — please review when you get a chance.'][mt_rand(0, 2)], 'created_at' => $created->copy()->addDay()]);
                ActivityLog::create(['user_id' => $e[$assignee]->user_id, 'action' => 'status', 'module' => 'tasks', 'record_id' => $task->id, 'description' => "{$e[$assignee]->full_name} changed status Pending → ".label($status), 'created_at' => $created->copy()->addDays(2)]);
            }
            if ($status === 'review') {
                $task->comments()->create(['user_id' => $u[$creator]->id, 'comment' => 'Thanks! I will review this today.', 'created_at' => $created->copy()->addDays(3)]);
            }

            if ($status === 'pending' && $due >= 0) {
                $e[$assignee]->user?->notifyNow(new TaskAssigned($task));
            }
        }
    }

    private function payroll(): void
    {
        $months = [today()->startOfMonth()->subMonths(2), today()->startOfMonth()->subMonth(), today()->startOfMonth()];
        $hr = $this->users['hr'];

        foreach ($months as $index => $period) {
            foreach ($this->employees as $employee) {
                if (Carbon::parse($employee->joining_date)->gt($period->copy()->endOfMonth())) {
                    continue;
                }

                $basic = (float) $employee->salary;
                $allowances = round($basic * 0.10, 2);
                $overtime = mt_rand(0, 3) === 0 ? mt_rand(50, 400) : 0;
                $bonus = $index === 1 && mt_rand(0, 2) === 0 ? mt_rand(200, 800) : 0;
                $deductions = mt_rand(0, 5) === 0 ? mt_rand(40, 200) : 0;
                $tax = round(($basic + $allowances) * 0.08, 2);
                $status = match ($index) {
                    0, 1 => 'paid',
                    default => mt_rand(0, 1) ? 'pending' : 'draft',
                };

                $payroll = Payroll::create([
                    'employee_id' => $employee->id,
                    'period' => $period->toDateString(),
                    'basic_salary' => $basic,
                    'allowances' => $allowances,
                    'overtime' => $overtime,
                    'bonus' => $bonus,
                    'deductions' => $deductions,
                    'tax' => $tax,
                    'other_deductions' => mt_rand(0, 6) === 0 ? 50 : 0,
                    'status' => $status,
                    'payment_method' => $status === 'paid' ? 'bank_transfer' : null,
                    'paid_at' => $status === 'paid' ? $period->copy()->endOfMonth()->subDay() : null,
                    'generated_by' => $hr->id,
                    'approved_by' => $status === 'paid' ? $hr->id : null,
                    'approved_at' => $status === 'paid' ? $period->copy()->endOfMonth()->subDays(3) : null,
                    'created_at' => $period->copy()->endOfMonth()->subDays(5),
                ]);

                if ($index === 1) {
                    $employee->user?->notifyNow(new PayrollGenerated($payroll, 'paid'));
                }
            }
        }
    }

    private function expenses(): void
    {
        $categories = ExpenseCategory::pluck('id', 'name');
        $creators = [$this->users['admin'], $this->users['hr'], $this->users['manager'], $this->users['manager2']];
        $items = [
            ['Printer paper & toner', 'Office Supplies', 180, 420],
            ['Client visit – train tickets', 'Travel', 120, 380],
            ['Electricity bill', 'Utilities', 650, 900],
            ['Internet service', 'Utilities', 150, 150],
            ['Office rent', 'Rent', 4500, 4500],
            ['External monitor purchase', 'Equipment', 220, 480],
            ['Air-conditioning service', 'Maintenance', 180, 350],
            ['Google Ads campaign', 'Marketing', 400, 1200],
            ['Team lunch – project launch', 'Other', 150, 320],
            ['Stationery restock', 'Office Supplies', 60, 140],
            ['Conference registration', 'Travel', 300, 700],
            ['Water & coffee supplies', 'Office Supplies', 80, 160],
        ];

        $receiptCount = 0;
        for ($m = 5; $m >= 0; $m--) {
            $month = today()->startOfMonth()->subMonths($m);
            foreach ($items as $i => [$title, $category, $min, $max]) {
                if ($category !== 'Rent' && $category !== 'Utilities' && mt_rand(0, 2) === 0) {
                    continue;
                }
                $date = $month->copy()->addDays(mt_rand(0, $m === 0 ? max(0, today()->day - 1) : 26));
                $status = $m === 0 && mt_rand(0, 2) === 0 ? 'pending' : (mt_rand(0, 12) === 0 ? 'rejected' : 'approved');
                $creator = $creators[mt_rand(0, count($creators) - 1)];
                $expense = Expense::create([
                    'title' => $title,
                    'expense_category_id' => $categories[$category],
                    'amount' => mt_rand($min * 100, $max * 100) / 100,
                    'date' => $date,
                    'payment_method' => ['bank_transfer', 'credit_card', 'cash', 'debit_card'][mt_rand(0, 3)],
                    'description' => self::DEMO_NOTE,
                    'created_by' => $creator->id,
                    'status' => $status,
                    'reviewed_by' => $status !== 'pending' ? $this->users['superadmin']->id : null,
                    'reviewed_at' => $status !== 'pending' ? $date->copy()->addDays(2) : null,
                    'review_note' => $status === 'rejected' ? 'Not within this month\'s budget.' : null,
                    'created_at' => $date,
                ]);

                if ($receiptCount < 4 && $m <= 1) {
                    $path = 'receipts/demo-receipt-'.$expense->id.'.pdf';
                    Storage::disk('local')->put($path, $this->pdf('Receipt', [
                        'Vendor' => 'Demo Supplier Inc.', 'Item' => $expense->title,
                        'Date' => $expense->date->format('M d, Y'), 'Amount' => money($expense->amount),
                    ]));
                    $expense->update(['receipt' => $path, 'receipt_name' => 'receipt-'.$expense->id.'.pdf']);
                    $receiptCount++;
                }
            }
        }
    }

    private function assets(): void
    {
        $e = $this->employees;
        $admin = $this->users['admin'];

        $assets = [
            ['Dell Latitude 7440', 'laptop', 'DL7440-8841', 1450, 'employee'],
            ['MacBook Pro 14"', 'laptop', 'C02FK3MBP14', 2399, 'e3'],
            ['Lenovo ThinkPad T14', 'laptop', 'PF3XT14-221', 1280, 'e2'],
            ['HP EliteBook 840', 'laptop', 'HP840-55120', 1190, 'e7'],
            ['Lenovo ThinkPad E14', 'laptop', 'PF4E14-009', 890, null],
            ['Dell UltraSharp 27" Monitor', 'monitor', 'DU27-99812', 420, 'employee'],
            ['LG 24" Monitor', 'monitor', 'LG24-11873', 180, 'e4'],
            ['iPhone 15', 'mobile', 'IP15-77120', 999, 'manager2'],
            ['Samsung Galaxy S23', 'mobile', 'SGS23-44102', 799, null],
            ['HP LaserJet Pro MFP', 'printer', 'HPLJ-330981', 540, null],
            ['Logitech MX Keys', 'keyboard', 'LMXK-55001', 110, 'e2'],
            ['Logitech MX Master 3S', 'mouse', 'LMX3S-66003', 99, 'e2'],
            ['Ergonomic office chair', 'office_furniture', null, 350, 'e8'],
            ['Standing desk', 'office_furniture', null, 620, 'manager'],
            ['Epson projector', 'other', 'EPX-778123', 760, null],
            ['Old Dell desktop', 'desktop', 'DOPT-2016-01', 700, null],
        ];

        foreach ($assets as $i => [$name, $category, $serial, $price, $assignee]) {
            $purchase = today()->subDays(mt_rand(60, 900));
            $asset = Asset::create([
                'asset_code' => 'AST-'.(1001 + $i),
                'name' => $name,
                'category' => $category,
                'serial_number' => $serial,
                'purchase_date' => $purchase,
                'purchase_price' => $price,
                'condition' => $name === 'Old Dell desktop' ? 'poor' : ['new', 'good', 'good', 'fair'][mt_rand(0, 3)],
                'location' => ['Head Office – Floor 1', 'Head Office – Floor 2', 'Storage Room', 'Meeting Room A'][mt_rand(0, 3)],
                'status' => 'available',
                'notes' => self::DEMO_NOTE,
            ]);

            if ($assignee) {
                $assignedAt = $purchase->copy()->addDays(mt_rand(1, 20));
                $asset->assignments()->create(['employee_id' => $e[$assignee]->id, 'assigned_by' => $admin->id, 'assigned_at' => $assignedAt, 'condition_on_assign' => $asset->condition]);
                $asset->update(['employee_id' => $e[$assignee]->id, 'status' => 'assigned']);
                ActivityLog::create(['user_id' => $admin->id, 'action' => 'assigned', 'module' => 'assets', 'record_id' => $asset->id, 'description' => "Assigned {$name} ({$asset->asset_code}) to {$e[$assignee]->full_name}", 'created_at' => $assignedAt]);
                if (in_array($assignee, ['employee', 'e2'])) {
                    $e[$assignee]->user?->notifyNow(new AssetAssigned($asset));
                }
            }
        }

        // History: a returned assignment, one item in maintenance, one retired.
        $spare = Asset::where('name', 'Lenovo ThinkPad E14')->first();
        $spare->assignments()->create(['employee_id' => $e['e10']->id, 'assigned_by' => $admin->id, 'assigned_at' => today()->subDays(120), 'returned_at' => today()->subDays(40), 'returned_to' => $admin->id, 'condition_on_assign' => 'good', 'condition_on_return' => 'good', 'notes' => 'Temporary laptop during onboarding.']);

        $printer = Asset::where('name', 'HP LaserJet Pro MFP')->first();
        $printer->maintenances()->create(['title' => 'Paper feed jam repair', 'description' => 'Rollers replaced by vendor.', 'vendor' => 'PrintFix Services', 'cost' => 85, 'started_at' => today()->subDays(3), 'logged_by' => $admin->id]);
        $printer->update(['status' => 'maintenance']);

        Asset::where('name', 'Old Dell desktop')->first()->update(['status' => 'retired']);
    }

    private function documents(): void
    {
        $e = $this->employees;
        $hr = $this->users['hr'];
        $today = today();

        $docs = [
            ['Employment Contract – Daniel Brooks', 'contract', 'employee', null, true],
            ['Passport copy – Daniel Brooks', 'id_document', 'employee', $today->copy()->addDays(21), true],
            ['Employment Contract – Aisha Khan', 'contract', 'e2', null, true],
            ['Work permit – Lucas Weber', 'id_document', 'e3', $today->copy()->addDays(12), true],
            ['AWS Certified Developer – Aisha Khan', 'certificate', 'e2', $today->copy()->addMonths(14), true],
            ['Fixed-term contract – Mateo Rossi', 'contract', 'e9', $today->copy()->subDays(4), true],
            ['Driving licence – Emma Thompson', 'id_document', 'e4', $today->copy()->addDays(75), true],
            ['Background check – Hannah Schmidt', 'employee_document', 'e10', null, false],
            ['Employee Handbook 2026', 'policy', null, null, true],
            ['Leave & Attendance Policy', 'policy', null, null, true],
            ['Office Lease Agreement', 'company_document', null, $today->copy()->addDays(28), false],
            ['Business Insurance Certificate', 'company_document', null, $today->copy()->addMonths(8), false],
        ];

        foreach ($docs as [$title, $category, $key, $expiry, $visible]) {
            $path = 'documents/demo-'.substr(md5($title), 0, 16).'.pdf';
            Storage::disk('local')->put($path, $this->pdf($title, [
                'Category' => label($category),
                'Employee' => $key ? $e[$key]->full_name : 'Company-wide',
                'Expiry' => $expiry ? $expiry->format('M d, Y') : 'No expiry',
            ]));

            $document = Document::create([
                'title' => $title,
                'category' => $category,
                'file_path' => $path,
                'original_name' => str($title)->slug().'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => Storage::disk('local')->size($path),
                'employee_id' => $key ? $e[$key]->id : null,
                'expiry_date' => $expiry,
                'employee_visible' => $visible,
                'description' => self::DEMO_NOTE,
                'uploaded_by' => $hr->id,
                'created_at' => $today->copy()->subDays(mt_rand(10, 200)),
            ]);

            if ($expiry && $expiry->lte($today->copy()->addDays(30))) {
                $hr->notifyNow(new DocumentExpiring($document->load('employee')));
                $document->forceFill(['expiry_notified_at' => now()])->saveQuietly();
            }
        }
    }

    private function activity(): void
    {
        $u = $this->users;
        $entries = [
            ['superadmin', 'updated', 'settings', 'Olivia Bennett updated system settings', 9],
            ['hr', 'created', 'employees', 'Created employee Hannah Schmidt (EMP-1015)', 8],
            ['admin', 'generated', 'payroll', 'Priya Sharma generated payroll for '.today()->subMonth()->format('F Y'), 6],
            ['hr', 'approved', 'leaves', "Priya Sharma approved Chloe Dubois's Annual Leave request", 4],
            ['manager', 'created', 'tasks', 'James Carter assigned "Migrate customer portal to new API" to Daniel Brooks', 3],
            ['admin', 'uploaded', 'documents', 'Uploaded document "Employee Handbook 2026"', 2],
            ['employee', 'check_in', 'attendance', 'Daniel Brooks checked in at 08:52 AM', 1],
        ];
        foreach ($entries as [$key, $action, $module, $description, $daysAgo]) {
            ActivityLog::create([
                'user_id' => $u[$key]->id, 'action' => $action, 'module' => $module, 'description' => $description,
                'ip_address' => '127.0.0.1', 'created_at' => now()->subDays($daysAgo)->subMinutes(mt_rand(0, 600)),
            ]);
        }

        ActivityLog::create(['user_id' => $u['superadmin']->id, 'action' => 'seeded', 'module' => 'system', 'description' => 'Demo data loaded (DemoDataSeeder) — all records are fictional', 'created_at' => now()]);
    }

    /** Small, real PDF so demo documents/receipts can be downloaded. */
    private function pdf(string $title, array $fields): string
    {
        $rows = collect($fields)->map(fn ($v, $k) => '<tr><td style="color:#666;padding:6px 16px 6px 0">'.e($k).'</td><td style="padding:6px 0"><b>'.e($v).'</b></td></tr>')->implode('');

        return Pdf::loadHTML('<div style="font-family:DejaVu Sans,sans-serif;padding:40px">'
            .'<div style="color:#4f46e5;font-weight:bold;font-size:12px;letter-spacing:2px">OFFICEPRO · DEMO DOCUMENT</div>'
            .'<h1 style="font-size:22px;margin:12px 0 24px">'.e($title).'</h1><table>'.$rows.'</table>'
            .'<p style="margin-top:40px;color:#999;font-size:11px">This file was generated by the OfficePro demo seeder and contains no real personal data.</p></div>')
            ->output();
    }
}
