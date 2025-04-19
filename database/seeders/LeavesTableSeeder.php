<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeavesTableSeeder extends Seeder
{
    public function run(): void
    {
        $otherTypes = [
            'Vacances personnelles',
            'Rendez-vous médical',
            'Urgence familiale',
            'Événement personnel',
        ];

        for ($i = 0; $i < 10; $i++) {
            $startDate = Carbon::now()->addDays(rand(-30, 30))->setTime(8, 0, 0)->toDateTimeString();
            $endDate = Carbon::now()->addDays(rand(31, 60))->setTime(8, 0, 0)->toDateTimeString();

            $leaveDays = rand(1, 10);

            DB::table('leaves')->insert([
                'user_id' => rand(1, 5),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'leave_type' => 'personal_leave',
                'other_type' => $otherTypes[array_rand($otherTypes)],
                'leave_days_requested' => $leaveDays,
                'effective_leave_days' => $leaveDays,
                'attachment_path' => null,
                'status' => 'on_hold',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
