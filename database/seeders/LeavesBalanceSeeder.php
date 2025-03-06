<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeavesBalanceSeeder extends Seeder
{
    public function run()
    {
        $users = DB::table('users')->where('role', '!=', 'admin')->get();

        foreach ($users as $user) {
            DB::table('leaves_balances')->insert([
                'user_id' => $user->id,
                'leave_day_limit' => 10,
                'description' => 'Initial leave balance',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
