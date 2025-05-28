<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientsTableSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            'France', 'Germany', 'Belgium', 'Canada', 'United States',
            'United Kingdom', 'Spain', 'Italy', 'Switzerland', 'Netherlands'
        ];

        $civilities = ['Mr.', 'Mrs.', 'Ms.'];
        $firstNames = ['John', 'Jane', 'Alice', 'Bob', 'Charlie', 'Emma', 'Liam', 'Sophia', 'Noah', 'Olivia'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];

        $companyNames = [
            'TechCorp', 'GlobalSoft', 'EcoSolutions', 'FinServe', 'MediPlus',
            'BuildRight', 'GreenEnergy', 'DataWorks', 'LogiTrans', 'SecureNet'
        ];

        // 10 individuals
        for ($i = 0; $i < 10; $i++) {
            $civility = $civilities[array_rand($civilities)];
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $fullName = trim("$civility $firstName $lastName");
            $country = $countries[array_rand($countries)];
            $email = 'individual' . ($i + 1) . '@gmail.com';
            $phone = '+33' . rand(600000000, 699999999);
            $postalCode = str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            $address = rand(1, 100) . ' Rue de Exemple, ' . $country;

            DB::table('clients')->insert([
                'client_type' => 'individual',
                'name' => $fullName,
                'tva_number_client' => null,
                'address' => $address,
                'postal_code' => $postalCode,
                'rib_bank' => null,
                'country' => $country,
                'email' => $email,
                'phone_number' => $phone,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 10 professionals
        for ($i = 0; $i < 10; $i++) {
            $companyName = $companyNames[$i];
            $country = $countries[array_rand($countries)];
            $email = 'professional' . ($i + 1) . '@gmail.com';
            $phone = '+33' . rand(600000000, 699999999);
            $postalCode = str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            $address = rand(1, 100) . ' Rue de Exemple, ' . $country;
            $tvaNumber = 19;
            $rib = 'FR76' . strtoupper(Str::random(23));

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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
