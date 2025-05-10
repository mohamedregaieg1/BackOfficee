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

        // L'ID 1 et 4 sont exclus, donc nous créons des congés pour les utilisateurs de 2 à 12
        $userIds = range(2, 12);

        foreach ($userIds as $userId) {
            // On crée 12 congés pour chaque utilisateur
            for ($i = 0; $i < 12; $i++) {
                // Définir le type de congé
                $leaveType = $leaveTypes[array_rand($leaveTypes)];

                // Générer les dates de début et de fin du congé avec l'heure à 08:00:00
                $startDate = Carbon::now()->addDays(rand(-30, 30))->setTime(8, 0, 0)->toDateTimeString();
                $endDate = Carbon::now()->addDays(rand(31, 60))->setTime(8, 0, 0)->toDateTimeString();

                // Nombre de jours de congé demandés
                $leaveDays = rand(1, 10);

                // Déterminer le statut et les jours effectifs
                $status = 'on_hold';
                $effectiveLeaveDays = $leaveDays;

                // Si le type de congé est sick_leave, ajuster les jours effectifs
                if ($leaveType === 'sick_leave') {
                    $effectiveLeaveDays = max(0, $leaveDays - 2);  // S'assurer que le nombre de jours n'est pas négatif
                }

                // Attribution du statut
                if ($i < 2) {
                    $status = 'approved';  // Les 2 premiers congés sont approuvés
                } elseif ($i === 2) {
                    $status = 'rejected';  // Le 3ème congé est rejeté
                }

                // On insère les données pour ce congé
                DB::table('leaves')->insert([
                    'user_id' => $userId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'leave_type' => $leaveType,
                    'other_type' => in_array($leaveType, ['personal_leave', 'other']) ? $otherTypes[array_rand($otherTypes)] : null,
                    'leave_days_requested' => $leaveDays,
                    'effective_leave_days' => $effectiveLeaveDays,
                    'attachment_path' => null,
                    'status' => $status,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
