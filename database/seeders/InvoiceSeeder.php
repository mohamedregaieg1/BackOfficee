<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\Service;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $paymentModes = ['bank transfer', 'credit card', 'cash', 'paypal', 'cheque', 'other'];
        $date = Carbon::now();

        $factureNumber = 'F/' . $date->format('mY') . '/1';

        $facture = Invoice::create([
            'type' => 'facture',
            'creation_date' => $date->copy()->subDays(rand(0, 30)),
            'number' => $factureNumber,
            'additional_date_type' => $faker->randomElement(['Date of sale', 'Expiry date', 'Withdrawal date until', null]),
            'additional_date' => $faker->optional()->date(),
            'company_name' => 'Procan',
            'company_id' => 1,
            'client_id' => 1,
            'payment_mode' => $faker->randomElement($paymentModes),
            'due_date' => $faker->optional()->date(),
            'payment_status' => $faker->randomElement(['paid', 'partially paid']),
            'amount_paid' => $faker->randomFloat(2, 100, 1000),
        ]);

        $devisNumber = 'D/' . $date->format('mY') . '/2';

        $devis = Invoice::create([
            'type' => 'devis',
            'creation_date' => $date->copy()->subDays(rand(0, 30)),
            'number' => $devisNumber,
            'additional_date_type' => $faker->randomElement(['Date of sale', 'Expiry date', 'Withdrawal date until', null]),
            'additional_date' => $faker->optional()->date(),
            'company_name' => 'Adequate',
            'company_id' => 2,
            'client_id' => rand(1, 10),
            'payment_mode' => $faker->randomElement($paymentModes),
            'due_date' => $faker->optional()->date(),
            'payment_status' => $faker->randomElement(['paid', 'partially paid']),
            'amount_paid' => $faker->randomFloat(2, 100, 1000),
        ]);


    }
}
