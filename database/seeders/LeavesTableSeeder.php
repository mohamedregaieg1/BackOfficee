<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeavesTableSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = ['sick_leave', 'personal_leave', 'other'];
        $otherTypes = ['Vacances personnelles', 'Rendez-vous médical', 'Urgence familiale', 'Événement personnel'];

        $users = DB::table('users')->whereNotIn('id', [1, 4])->get();
        $years = [2025, 2024, 2023];
        $summerMonths = [5, 6, 7, 8];
        $shoulderMonths = [4, 9, 10];
        $winterMonths = [1, 2, 3, 11, 12];

        $today = Carbon::today();
        $usersWithLeaveToday = 0;

        foreach ($users as $user) {
            $userId = $user->id;
            $gender = $user->gender;
            $existingLeaves = [];

            foreach ($years as $year) {
                // === ONE paternity/maternity per year ===
                if ($gender === 'male') {
                    $start = Carbon::create($year, rand(6, 8), rand(1, 10), 8);
                    $end = $start->copy()->addDays(24);

                    if ($this->coversToday($start, $end, $today)) {
                        if ($usersWithLeaveToday >= 2) {
                            $start = $today->copy()->addDays(rand(1, 30));
                            $end = $start->copy()->addDays(24);
                        } else {
                            $usersWithLeaveToday++;
                        }
                    }

                    $this->addLeave($userId, $start, $end, 'paternity_leave', null, 25, 25, 'approved');
                    $existingLeaves[] = [$start, $end];
                } elseif ($gender === 'female') {
                    $start = Carbon::create($year, rand(4, 6), rand(1, 10), 8);
                    $end = $start->copy()->addDays(89);

                    if ($this->coversToday($start, $end, $today)) {
                        if ($usersWithLeaveToday >= 2) {
                            $start = $today->copy()->addDays(rand(1, 30));
                            $end = $start->copy()->addDays(89);
                        } else {
                            $usersWithLeaveToday++;
                        }
                    }

                    $this->addLeave($userId, $start, $end, 'maternity_leave', null, 90, 90, 'approved');
                    $existingLeaves[] = [$start, $end];
                }

                // === Additional short leaves ===
                $leaveCount = rand(14, 20);
                $attempts = 0; // Prevent infinite loops

                while (count($existingLeaves) < $leaveCount && $attempts < $leaveCount * 2) {
                    $attempts++;
                    $month = match (true) {
                        count($existingLeaves) < 8 => collect($summerMonths)->random(),
                        count($existingLeaves) < 12 => collect($shoulderMonths)->random(),
                        default => collect($winterMonths)->random(),
                    };
                    $day = rand(1, 20);
                    $startDate = Carbon::create($year, $month, $day, 8);

                    $leaveType = $leaveTypes[array_rand($leaveTypes)];
                    $maxDays = match ($leaveType) {
                        'sick_leave' => rand(1, 5),
                        'personal_leave' => rand(2, 10),
                        'other' => rand(1, 7),
                        default => rand(1, 5)
                    };
                    $leaveDays = rand(1, $maxDays);
                    $endDate = $startDate->copy()->addDays($leaveDays - 1);

                    // Check for overlap
                    if ($this->overlapsExisting($startDate, $endDate, $existingLeaves)) {
                        continue; // skip if overlaps
                    }

                    if ($this->coversToday($startDate, $endDate, $today)) {
                        if ($usersWithLeaveToday >= 2) {
                            $startDate = $today->copy()->addDays(rand(1, 30));
                            $endDate = $startDate->copy()->addDays($leaveDays - 1);
                        } else {
                            $usersWithLeaveToday++;
                        }
                    }

                    $status = count($existingLeaves) < 10 ? 'approved' : (count($existingLeaves) === 10 ? 'rejected' : 'on_hold');
                    $effective = $leaveType === 'sick_leave' ? max(1, $leaveDays - rand(1, 2)) : $leaveDays;

                    $this->addLeave($userId, $startDate, $endDate, $leaveType,
                        in_array($leaveType, ['personal_leave', 'other']) ? $otherTypes[array_rand($otherTypes)] : null,
                        $leaveDays, $effective, $status);

                    $existingLeaves[] = [$startDate, $endDate];
                }
            }
        }
    }

    private function addLeave($userId, $start, $end, $type, $otherType, $requested, $effective, $status)
    {
        DB::table('leaves')->insert([
            'user_id' => $userId,
            'start_date' => $start,
            'end_date' => $end,
            'leave_type' => $type,
            'other_type' => $otherType,
            'leave_days_requested' => $requested,
            'effective_leave_days' => $effective,
            'attachment_path' => null,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function coversToday($start, $end, $today)
    {
        return $today->between($start, $end);
    }

    private function overlapsExisting($newStart, $newEnd, $existingLeaves)
    {
        foreach ($existingLeaves as [$existingStart, $existingEnd]) {
            if ($newStart->lte($existingEnd) && $newEnd->gte($existingStart)) {
                return true; // overlap detected
            }
        }
        return false;
    }
}
