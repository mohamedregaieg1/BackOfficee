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
                'name' => 'Fête de l’Indépendance',
                'start_date' => "$year-03-20",
                'end_date' => "$year-03-20",
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
                'name' => 'Fête de la Femme',
                'start_date' => "$year-08-13",
                'end_date' => "$year-08-13",
            ],
        ];

        $mobileHolidays = [
            [
                'name' => 'Aïd el-Fitr',
                'start_date' => "$year-04-21", // 21 avril 2025
                'end_date' => "$year-04-21",
            ],
            [
                'name' => 'Aïd el-Adha',
                'start_date' => "$year-06-27", // 27 juin 2025
                'end_date' => "$year-06-27",
            ],
            [
                'name' => 'Nouvel An Hégire',
                'start_date' => "$year-07-17", // 17 juillet 2025
                'end_date' => "$year-07-17",
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
