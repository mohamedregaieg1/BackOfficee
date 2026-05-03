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
        $statuses = ['approved', 'rejected', 'on_hold'];

        $users = DB::table('users')->whereNotIn('id', [1, 4])->get();
        $years = [2024, 2025, 2026];
        $summerMonths = [5, 6, 7, 8];
        $shoulderMonths = [4, 9, 10];
        $winterMonths = [1, 2, 3, 11, 12];

        $today = Carbon::today();
        $currentMonth = $today->month;
        $currentYear = $today->year;
        $leaveTodayCreated = false;
        $userWithLeaveToday = null;
        $totalLeavesCreated = 0;

        // Charger les balances de tous les users
        $balances = DB::table('leaves_balances')
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->groupBy('user_id');

        foreach ($users as $user) {
            $userId = $user->id;
            $gender = $user->gender;
            $existingLeaves = [];

            // Initialiser les compteurs de jours utilisés par type
            $usedDays = [
                'sick_leave' => 0,
                'personal_leave' => 0,
                'other' => 0,
                'paternity_leave' => 0,
                'maternity_leave' => 0,
            ];

            // Récupérer la balance de l'utilisateur
            $userBalances = $balances->get($userId) ?? collect();

            foreach ($years as $year) {
                $maxMonth = ($year == $currentYear) ? $currentMonth : 12;

                // === Maternity leave tous les 9 mois pour les femmes ===
                if ($gender === 'female') {
                    $maternityMonths = [];
                    for ($m = 1; $m <= $maxMonth; $m += 9) {
                        $maternityMonths[] = $m;
                    }

                    foreach ($maternityMonths as $maternityMonth) {
                        // Vérifier la balance maternity
                        $maternityBalance = $userBalances->where('leave_type', 'maternity_leave')->first();
                        $maxMaternityDays = $maternityBalance ? $maternityBalance->balance : 90;

                        if ($usedDays['maternity_leave'] + 90 > $maxMaternityDays) {
                            continue; // Skip si dépasse la balance
                        }

                        $start = Carbon::create($year, $maternityMonth, rand(1, min(10, 28)), 8);

                        if ($year == $currentYear && $start->gt($today)) {
                            continue;
                        }

                        $end = $start->copy()->addDays(89);

                        if ($year == $currentYear && $end->gt($today)) {
                            $end = $today->copy();
                            $start = $end->copy()->subDays(89);
                        }

                        // Ne pas créer de congé aujourd'hui si déjà fait
                        if ($this->coversToday($start, $end, $today)) {
                            if ($leaveTodayCreated) {
                                continue;
                            }
                            $leaveTodayCreated = true;
                            $userWithLeaveToday = $userId;
                        }

                        $this->addLeave($userId, $start, $end, 'maternity_leave', null, 90, 90, 'approved');
                        $existingLeaves[] = [$start, $end];
                        $usedDays['maternity_leave'] += 90;
                        $totalLeavesCreated++;
                    }
                }

                // === Paternity leave une fois par an pour les hommes ===
                if ($gender === 'male') {
                    // Vérifier la balance paternity
                    $paternityBalance = $userBalances->where('leave_type', 'paternity_leave')->first();
                    $maxPaternityDays = $paternityBalance ? $paternityBalance->balance : 25;

                    if ($usedDays['paternity_leave'] + 25 <= $maxPaternityDays) {
                        $paternityMonth = rand(6, min(8, $maxMonth));
                        if ($paternityMonth > $maxMonth) {
                            $paternityMonth = $maxMonth;
                        }

                        $start = Carbon::create($year, $paternityMonth, rand(1, min(10, 28)), 8);

                        if ($year == $currentYear && $start->gt($today)) {
                            $start = $today->copy()->subDays(rand(0, 30));
                        }

                        $end = $start->copy()->addDays(24);

                        if ($year == $currentYear && $end->gt($today)) {
                            $end = $today->copy();
                            $start = $end->copy()->subDays(24);
                        }

                        // Ne pas créer de congé aujourd'hui si déjà fait
                        if ($this->coversToday($start, $end, $today)) {
                            if ($leaveTodayCreated) {
                                // Décaler pour éviter aujourd'hui
                                $start = $today->copy()->addDays(rand(1, 30));
                                $end = $start->copy()->addDays(24);
                            } else {
                                $leaveTodayCreated = true;
                                $userWithLeaveToday = $userId;
                            }
                        }

                        $this->addLeave($userId, $start, $end, 'paternity_leave', null, 25, 25, 'approved');
                        $existingLeaves[] = [$start, $end];
                        $usedDays['paternity_leave'] += 25;
                        $totalLeavesCreated++;
                    }
                }

                // === Beaucoup de congés par mois avec tous les statuts ===
                $monthsToProcess = ($year == $currentYear) ? range(1, $currentMonth) : range(1, 12);

                foreach ($monthsToProcess as $month) {
                    $leavesPerMonth = rand(3, 6);

                    for ($i = 0; $i < $leavesPerMonth; $i++) {
                        $day = rand(1, min(25, 28));

                        try {
                            $startDate = Carbon::create($year, $month, $day, 8);
                        } catch (\Exception $e) {
                            $day = 28;
                            $startDate = Carbon::create($year, $month, min($day, 28), 8);
                        }

                        if ($year == $currentYear && $startDate->gt($today)) {
                            continue;
                        }

                        $leaveType = $leaveTypes[array_rand($leaveTypes)];

                        // Vérifier la balance pour ce type de congé
                        $balanceForType = $userBalances->where('leave_type', $leaveType)->first();
                        $maxDaysForType = $balanceForType ? $balanceForType->balance : 20; // 20 par défaut

                        $remainingDays = $maxDaysForType - $usedDays[$leaveType];
                        if ($remainingDays <= 0) {
                            continue; // Skip si plus de jours disponibles
                        }

                        $maxDays = min(match ($leaveType) {
                            'sick_leave' => rand(1, 5),
                            'personal_leave' => rand(2, 10),
                            'other' => rand(1, 7),
                            default => rand(1, 5)
                        }, $remainingDays);

                        $leaveDays = rand(1, $maxDays);
                        $endDate = $startDate->copy()->addDays($leaveDays - 1);

                        if ($year == $currentYear && $endDate->gt($today)) {
                            $endDate = $today->copy();
                            $leaveDays = $startDate->diffInDays($endDate) + 1;
                            if ($leaveDays < 1) {
                                continue;
                            }
                        }

                        if ($this->overlapsExisting($startDate, $endDate, $existingLeaves)) {
                            continue;
                        }

                        // Gestion du congé aujourd'hui : UN SEUL utilisateur
                        if ($this->coversToday($startDate, $endDate, $today)) {
                            if (!$leaveTodayCreated) {
                                $leaveTodayCreated = true;
                                $userWithLeaveToday = $userId;
                            } else {
                                // Décaler ce congé pour éviter aujourd'hui
                                $startDate = $today->copy()->subDays(rand(7, 30));
                                $endDate = $startDate->copy()->addDays($leaveDays - 1);
                                if ($endDate->gte($today)) {
                                    $endDate = $today->copy()->subDay();
                                    $leaveDays = $startDate->diffInDays($endDate) + 1;
                                }
                                if ($leaveDays < 1) continue;
                            }
                        }

                        $statusIndex = $i % 3;
                        $status = $statuses[$statusIndex];

                        if ($month == $currentMonth && $year == $currentYear) {
                            $currentMonthStatuses = ['approved', 'rejected', 'on_hold'];
                            $status = $currentMonthStatuses[$i % 3];
                        }

                        $effective = $status === 'approved' ? $leaveDays : ($status === 'rejected' ? 0 : $leaveDays);
                        if ($leaveType === 'sick_leave' && $status === 'approved') {
                            $effective = max(1, $leaveDays - rand(1, 2));
                        }

                        // Mettre à jour les jours utilisés seulement si approuvé
                        if ($status === 'approved') {
                            $usedDays[$leaveType] += $effective;
                        }

                        $this->addLeave($userId, $startDate, $endDate, $leaveType,
                            in_array($leaveType, ['personal_leave', 'other']) ? $otherTypes[array_rand($otherTypes)] : null,
                            $leaveDays, $effective, $status);

                        $existingLeaves[] = [$startDate, $endDate];
                        $totalLeavesCreated++;
                    }
                }
            }
        }

        // Log pour vérifier
        echo "Total leaves created: $totalLeavesCreated\n";
        if ($leaveTodayCreated) {
            echo "Un seul utilisateur avec congé aujourd'hui: User ID: $userWithLeaveToday\n";
        } else {
            echo "Aucun congé aujourd'hui trouvé, création forcée...\n";
            $randomUser = $users->random();
            $todayLeave = Carbon::today()->setHour(8);

            $this->addLeave(
                $randomUser->id,
                $todayLeave->copy()->subDays(rand(1, 3)),
                $todayLeave,
                'personal_leave',
                'Vacances personnelles',
                $todayLeave->diffInDays($todayLeave->copy()->subDays(rand(1, 3))) + 1,
                $todayLeave->diffInDays($todayLeave->copy()->subDays(rand(1, 3))) + 1,
                'approved'
            );

            echo "Congé aujourd'hui créé pour: {$randomUser->first_name} {$randomUser->last_name} (ID: {$randomUser->id})\n";
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
            'created_at' => $start,
            'updated_at' => $start,
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
                return true;
            }
        }
        return false;
    }
}
