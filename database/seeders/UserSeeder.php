<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
                        'first_name' => 'Admin',
                        'last_name' => 'Damak',
                        'username' => 'admin',
                        'email' => 'mohamedregaieg54057@gmail.com',
                        'password' => Hash::make('admin123'),
                        'role' => 'admin',
                        'gender' => 'male',
                        'company' => 'procan',
                        'start_date' => now(),
                        'initial_leave_balance' => 0,
                    ]);
    }
}
            
