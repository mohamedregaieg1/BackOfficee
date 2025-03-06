<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FixedLeaves;

class FixedLeavesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fixedLeaves = [
            ['leave_type' => 'paternity_leave', 'max_days' => 10],
            ['leave_type' => 'maternity_leave', 'max_days' => 90],
            ['leave_type' => 'sick_leave', 'max_days' => 30]
        ];

        foreach ($fixedLeaves as $leave) {
            FixedLeaves::updateOrCreate(
                ['leave_type' => $leave['leave_type']],
                ['max_days' => $leave['max_days']]
            );
        }
    }
}
