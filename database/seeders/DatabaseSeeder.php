<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FixedLeaves;
use App\Models\Comapny;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(FixedLeavesSeeder::class);
        $this->call(LeavesBalanceSeeder::class);
        $this->call(CompaniesTableSeeder::class);
        $this->call(LeavesTableSeeder::class);
        $this->call(ClientsTableSeeder::class);





    }
}
