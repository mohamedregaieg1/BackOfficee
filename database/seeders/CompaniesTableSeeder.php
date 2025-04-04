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
                'phone_number' => '+216 12345678',
                'address' => '123 Rue Principale, Tunis',
                'country' => 'Tunisia',
                'postal_code' => '1000',
                'tva_number' => '123456789',
                'rib_bank' => 'ABC123456789',
                'email' => 'contact@procan.com',
                'website' => 'https://procan.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Adequate',
                'phone_number' => '+216 87654321',
                'address' => '456 Avenue Secondaire, Sfax',
                'country' => 'France',
                'postal_code' => '3000',
                'tva_number' => '987654321',
                'rib_bank' => 'DEF987654321',
                'email' => 'contact@adequate.com',
                'website' => 'https://adequate.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
