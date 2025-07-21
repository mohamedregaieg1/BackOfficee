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
            'image_path' => null,
            'tva_number' => null,
            'address' => null,
            'postal_code' => null,
            'country' => 'Tunisia',
            'rib_bank' => null,
            'email' => null,
            'website' => null,
            'phone_number' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Adequate',
            'image_path' => null,
            'tva_number' => 123456789,
            'address' => '123 Rue de la République, Paris',
            'postal_code' => '75001',
            'country' => 'France',
            'rib_bank' => 'FR7612345987654321',
            'email' => 'contact@adequate.fr',
            'website' => 'https://www.adequate.fr',
            'phone_number' => '+33123456789',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
}

}
