<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            [
                'name' => 'Fête de l’Indépendance',
                'start_date' => "$year-03-20",
                'end_date' => "$year-03-20",
            ],
        ];

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
