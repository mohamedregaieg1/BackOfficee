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
            [
                'name' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin'), // Mot de passe crypté
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'employe',
                'email' => 'mohamedregaieg54057@gmail.com',
                'password' => Hash::make('employe'), // Mot de passe crypté
                'role' => 'employe',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'rh',
                'email' => 'rh@example.com',
                'password' => Hash::make('rh'), // Mot de passe crypté
                'role' => 'rh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
