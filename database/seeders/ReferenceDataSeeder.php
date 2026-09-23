<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\ExpenseCategory;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            ['name' => 'Annual Leave', 'code' => 'AL', 'days_per_year' => 20, 'is_paid' => true, 'requires_attachment' => false, 'color' => 'primary'],
            ['name' => 'Sick Leave', 'code' => 'SL', 'days_per_year' => 10, 'is_paid' => true, 'requires_attachment' => false, 'color' => 'danger'],
            ['name' => 'Casual Leave', 'code' => 'CL', 'days_per_year' => 7, 'is_paid' => true, 'requires_attachment' => false, 'color' => 'info'],
            ['name' => 'Emergency Leave', 'code' => 'EL', 'days_per_year' => 3, 'is_paid' => true, 'requires_attachment' => false, 'color' => 'warning'],
            ['name' => 'Unpaid Leave', 'code' => 'UL', 'days_per_year' => 0, 'is_paid' => false, 'requires_attachment' => false, 'color' => 'secondary'],
        ];
        foreach ($leaveTypes as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type + ['status' => 'active']);
        }

        foreach (['Office Supplies', 'Travel', 'Utilities', 'Rent', 'Equipment', 'Maintenance', 'Marketing', 'Other'] as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name], ['status' => 'active']);
        }

        foreach ([
            'CEO' => 'Chief executive officer', 'Manager' => 'Department or team manager', 'HR Manager' => 'Leads human resources',
            'HR Officer' => 'Human resources operations', 'Accountant' => 'Finance and bookkeeping', 'Developer' => 'Software engineering',
            'Designer' => 'UI / graphic design', 'Sales Executive' => 'Sales and client relations', 'Marketing Officer' => 'Marketing campaigns',
            'Office Assistant' => 'Office administration support', 'Office Administrator' => 'Runs daily office operations',
        ] as $name => $description) {
            Designation::firstOrCreate(['name' => $name], ['description' => $description, 'status' => 'active']);
        }
    }
}
