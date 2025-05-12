<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeavesTableSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            'paternity_leave',
            'maternity_leave',
            'sick_leave',
            'personal_leave',
            'other'
        ];

        $otherTypes = [
            'Vacances personnelles',
            'Rendez-vous médical',
            'Urgence familiale',
            'Événement personnel',
        ];

        // Utilisateurs sauf 1 et 4
        $users = DB::table('users')->whereNotIn('id', [1, 4])->get();

        foreach ($users as $user) {
            $userId = $user->id;
            $gender = $user->gender;

            // Congé de paternité ou maternité unique
            if ($gender === 'male') {
                $start = Carbon::now()->startOfMonth()->setTime(8, 0, 0);
                $end = $start->copy()->addDays(24)->setTime(8, 0, 0);
                DB::table('leaves')->insert([
                    'user_id' => $userId,
                    'start_date' => $start,
                    'end_date' => $end,
                    'leave_type' => 'paternity_leave',
                    'other_type' => null,
                    'leave_days_requested' => 25,
                    'effective_leave_days' => 25,
                    'attachment_path' => null,
                    'status' => 'approved',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($gender === 'female') {
                $start = Carbon::now()->startOfMonth()->setTime(8, 0, 0);
                $end = $start->copy()->addDays(89)->setTime(8, 0, 0);
                DB::table('leaves')->insert([
                    'user_id' => $userId,
                    'start_date' => $start,
                    'end_date' => $end,
                    'leave_type' => 'maternity_leave',
                    'other_type' => null,
                    'leave_days_requested' => 90,
                    'effective_leave_days' => 90,
                    'attachment_path' => null,
                    'status' => 'approved',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 11 congés divers
            for ($i = 0; $i < 11; $i++) {
                $leaveType = $leaveTypes[array_rand($leaveTypes)];
                $maxDays = in_array($leaveType, ['paternity_leave', 'personal_leave', 'other']) ? 7 : rand(3, 15);
                $leaveDays = rand(1, $maxDays);

                $startDate = Carbon::now()->subDays(rand(4, 6))->setTime(8, 0, 0);
                $endDate = $startDate->copy()->addDays($leaveDays - 1)->setTime(8, 0, 0);

                $status = 'on_hold';
                if ($i < 2) {
                    $status = 'approved';
                } elseif ($i === 2) {
                    $status = 'rejected';
                }

                $effective = ($leaveType === 'sick_leave') ? max(0, $leaveDays - 2) : $leaveDays;

                DB::table('leaves')->insert([
                    'user_id' => $userId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'leave_type' => $leaveType,
                    'other_type' => in_array($leaveType, ['personal_leave', 'other']) ? $otherTypes[array_rand($otherTypes)] : null,
                    'leave_days_requested' => $leaveDays,
                    'effective_leave_days' => $effective,
                    'attachment_path' => null,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
