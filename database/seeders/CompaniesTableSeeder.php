<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompaniesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insérer des données initiales dans la table companies
        DB::table('companies')->insert([
            [
                'name' => 'Procan',
                'country' => 'Tunisia',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Adequate',
                'country' => 'France',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
