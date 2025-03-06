<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FixedLeaves;
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

    }
}
