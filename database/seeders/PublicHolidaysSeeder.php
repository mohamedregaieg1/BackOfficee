<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicHolidaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $year = Carbon::now()->year;

        // Jours fériés fixes en Tunisie
        $holidays = [
            [
                'name' => 'Nouvel An',
                'start_date' => "$year-01-01",
                'end_date' => "$year-01-01",
            ],
            [
                'name' => 'Fête de la Révolution',
                'start_date' => "$year-01-14",
                'end_date' => "$year-01-14",
            ],
            [
                'name' => 'Fête de l\'Indépendance',
                'start_date' => "$year-03-20",
                'end_date' => "$year-03-20",
            ],
            [
                'name' => 'Fête des Martyrs',
                'start_date' => "$year-04-09",
                'end_date' => "$year-04-09",
            ],
            [
                'name' => 'Fête du Travail',
                'start_date' => "$year-05-01",
                'end_date' => "$year-05-01",
            ],
            [
                'name' => 'Fête de la République',
                'start_date' => "$year-07-25",
                'end_date' => "$year-07-25",
            ],
            [
                'name' => 'Fête de la Femme et de la Famille',
                'start_date' => "$year-08-13",
                'end_date' => "$year-08-13",
            ],
            [
                'name' => 'Fête de l\'Évacuation',
                'start_date' => "$year-10-15",
                'end_date' => "$year-10-15",
            ],
        ];

        // Jours fériés mobiles en Tunisie (basés sur le calendrier hégire) - Estimations pour 2026
        $mobileHolidays = [
            [
                'name' => 'Aïd el-Fitr',
                'start_date' => "$year-03-20", // Estimation 20 mars 2026 (1447 AH)
                'end_date' => "$year-03-22",   // 3 jours de congé
            ],
            [
                'name' => 'Aïd el-Adha (Aïd el-Kebir)',
                'start_date' => "$year-05-27", // Estimation 27 mai 2026 (1447 AH)
                'end_date' => "$year-05-29",   // 3 jours de congé
            ],
            [
                'name' => 'Ras el-Am el-Hijri (Nouvel An Hégire)',
                'start_date' => "$year-06-16", // Estimation 16 juin 2026 (1448 AH)
                'end_date' => "$year-06-16",   // 1 jour
            ],
            [
                'name' => 'Mouled (Naissance du Prophète)',
                'start_date' => "$year-08-25", // Estimation 25 août 2026 (1448 AH)
                'end_date' => "$year-08-25",   // 1 jour
            ],
        ];

        $holidays = array_merge($holidays, $mobileHolidays);

        foreach ($holidays as &$holiday) {
            $start = Carbon::parse($holiday['start_date']);
            $end = Carbon::parse($holiday['end_date']);
            $holiday['number_of_days'] = $start->diffInDays($end) + 1;
            $holiday['created_at'] = now();
            $holiday['updated_at'] = now();
        }

        DB::table('public_holidays')->insert($holidays);
    }
}
