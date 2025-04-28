<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        //  Création d'un seul admin
        DB::table('users')->insert([
            'first_name' => 'Admin',
            'last_name' => 'Damak',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'gender' => 'male',
            'company' => 'procan',
            'job_description' => 'Administrator',
            'start_date' => now(),
            'leave_balance' => 0,
            'avatar_path'=>'http://127.0.0.1:8000/dist/img/avatarmale.png',

        ]);

        // Création des 7 autres utilisateurs (Employee et HR)
        $users = [
            [
                'first_name' => 'Ali',
                'last_name' => 'Ben Salah',
                'username' => 'ali.bensalah',
                'email' => 'ali@example.com',
                'password' => Hash::make('ali123'),
                'role' => 'employee',
                'gender' => 'male',
                'company' => 'procan',
                'job_description' => 'Developer',
                'avatar_path'=>'http://127.0.0.1:8000/dist/img/avatarmale.png',
            ],
            [
                'first_name' => 'Sara',
                'last_name' => 'Bourguiba',
                'username' => 'sara.bourguiba',
                'email' => 'sara@example.com',
                'password' => Hash::make('sara123'),
                'role' => 'employee',
                'gender' => 'female',
                'company' => 'adequate',
                'job_description' => 'Designer',
                'avatar_path'=>'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            ],
            [
                'first_name' => 'Karim',
                'last_name' => 'Trabelsi',
                'username' => 'karim.trabelsi',
                'email' => 'karim@example.com',
                'password' => Hash::make('karim123'),
                'role' => 'employee',
                'gender' => 'male',
                'company' => 'procan',
                'job_description' => 'Project Manager',
                'avatar_path'=>'http://127.0.0.1:8000/dist/img/avatarmale.png',
            ],
            [
                'first_name' => 'Fatma',
                'last_name' => 'Zaoui',
                'username' => 'fatma.zaoui',
                'email' => 'fatma@example.com',
                'password' => Hash::make('fatma123'),
                'role' => 'employee',
                'gender' => 'female',
                'company' => 'adequate',
                'job_description' => 'HR Assistant',
                'avatar_path'=>'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            ],
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Gharbi',
                'username' => 'ahmed.gharbi',
                'email' => 'ahmed@example.com',
                'password' => Hash::make('ahmed123'),
                'role' => 'hr',
                'gender' => 'male',
                'company' => 'procan',
                'job_description' => 'HR Manager',
                'avatar_path'=>'http://127.0.0.1:8000/dist/img/avatarmale.png',

            ],
            [
                'first_name' => 'Leila',
                'last_name' => 'Mansouri',
                'username' => 'leila.mansouri',
                'email' => 'leila@example.com',
                'password' => Hash::make('leila123'),
                'role' => 'hr',
                'gender' => 'female',
                'company' => 'adequate',
                'job_description' => 'HR Specialist',
                'avatar_path'=>'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            ],
            [
                'first_name' => 'Omar',
                'last_name' => 'Jaziri',
                'username' => 'omar.jaziri',
                'email' => 'omar@example.com',
                'password' => Hash::make('Omar123'),
                'role' => 'employee',
                'gender' => 'male',
                'company' => 'procan',
                'job_description' => 'Technician',
                'avatar_path'=>'http://127.0.0.1:8000/dist/img/avatarmale.png',
            ],
        ];

        DB::table('users')->insert(array_map(function ($user) {
            return array_merge($user, [
                'start_date' => now(),
                'leave_balance' => 10,
            ]);
        }, $users));
    }
}
