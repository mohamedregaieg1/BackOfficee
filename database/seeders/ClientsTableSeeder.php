<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClientsTableSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            'Tunisia', 'France', 'Germany', 'Belgium', 'Canada',
            'United States', 'United Kingdom', 'Spain', 'Italy', 'Switzerland'
        ];

        // 10 individus (clients particuliers)
        for ($i = 0; $i < 10; $i++) {
            $clientNumber = $i + 1;
            $clientName = 'Client ' . $clientNumber;
            $country = $countries[array_rand($countries)];
            $email = 'client' . $clientNumber . '@test.com';
            $phone = '+216' . rand(20000000, 99999999);
            $postalCode = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $address = rand(1, 200) . ' Rue de Client ' . $clientNumber . ', ' . $country;

            // Date de création aléatoire entre 2024 et 2026
            $createdAt = Carbon::create(rand(2024, 2026), rand(1, 12), rand(1, 28));

            DB::table('clients')->insert([
                'client_type' => 'individual',
                'name' => $clientName,
                'tva_number_client' => null,
                'address' => $address,
                'postal_code' => $postalCode,
                'rib_bank' => null,
                'country' => $country,
                'email' => $email,
                'phone_number' => $phone,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // 10 professionnels (entreprises)
        for ($i = 0; $i < 10; $i++) {
            $societeNumber = $i + 1;
            $companyName = 'Société ' . $societeNumber;
            $country = $countries[array_rand($countries)];
            $email = 'societe' . $societeNumber . '@test.com';
            $phone = '+216' . rand(70000000, 79999999);
            $postalCode = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $address = rand(1, 200) . ' Avenue de la Société ' . $societeNumber . ', ' . $country;

            // TVA Number avec pourcentage 19%
            $tvaNumber = 19 ;

            // RIB format tunisien (20 chiffres)
            $rib = 'TN59' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT) .
                   str_pad(rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);

            // Date de création aléatoire entre 2024 et 2026
            $createdAt = Carbon::create(rand(2024, 2026), rand(1, 12), rand(1, 28));

            DB::table('clients')->insert([
                'client_type' => 'professional',
                'name' => $companyName,
                'tva_number_client' => $tvaNumber,
                'address' => $address,
                'postal_code' => $postalCode,
                'rib_bank' => $rib,
                'country' => $country,
                'email' => $email,
                'phone_number' => $phone,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
