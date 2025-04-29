<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $quantity = fake()->randomFloat(1, 1, 10);
            $priceHt = fake()->randomFloat(2, 100, 1000);
            $tva = fake()->randomElement([0, 7, 13, 19]);
            $totalHt = $quantity * $priceHt;
            $totalTtc = $totalHt * (1 + $tva / 100);

            Service::create([
                'name' => fake()->sentence(3),
                'quantity' => $quantity,
                'unit' => fake()->optional()->word(),
                'price_ht' => $priceHt,
                'tva' => $tva,
                'total_ht' => $totalHt,
                'total_ttc' => $totalTtc,
                'comment' => fake()->optional()->sentence(),
                'invoice_id' => rand(1, 2),
            ]);
        }
    }
}
