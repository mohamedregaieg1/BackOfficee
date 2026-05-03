<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Création d'un seul admin
        DB::table('users')->insert([
            'first_name' => 'Admin',
            'last_name' => 'Système',
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'gender' => 'male',
            'company' => 'company X',
            'job_description' => 'Administrateur Système',
            'start_date' => Carbon::create(2025, 11, 15),
            'phone' => '20123456',
            'address' => 'Tunis, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2025, 11, 15),
        ]);

        // Création des utilisateurs avec le rôle 'hr'
        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '1',
            'username' => 'employee1',
            'email' => 'employee1@test.com',
            'password' => Hash::make('employee1'),
            'role' => 'hr',
            'gender' => 'male',
            'company' => 'company X',
            'job_description' => 'Responsable Informatique',
            'start_date' => Carbon::create(2025, 11, 20),
            'phone' => '21123456',
            'address' => 'Monastir, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2025, 11, 20),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '2',
            'username' => 'employee2',
            'email' => 'employee2@test.com',
            'password' => Hash::make('employee2'),
            'role' => 'hr',
            'gender' => 'female',
            'company' => 'company X',
            'job_description' => 'Chef de Projet IT',
            'start_date' => Carbon::create(2026, 1, 10),
            'phone' => '22123456',
            'address' => 'Tunis, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2026, 1, 10),
        ]);

        // Création d'un utilisateur avec le rôle 'accountant'
        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '3',
            'username' => 'employee3',
            'email' => 'employee3@test.com',
            'password' => Hash::make('employee3'),
            'role' => 'accountant',
            'gender' => 'female',
            'company' => 'company X',
            'job_description' => 'Analyste Développeur',
            'start_date' => Carbon::create(2026, 2, 15),
            'phone' => '23123456',
            'address' => 'Sfax, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2026, 2, 15),
        ]);

        // Création des utilisateurs avec le rôle 'employee'
        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '4',
            'username' => 'employee4',
            'email' => 'employee4@test.com',
            'password' => Hash::make('employee4'),
            'role' => 'employee',
            'gender' => 'male',
            'company' => 'company X',
            'job_description' => 'Développeur Full Stack',
            'start_date' => Carbon::create(2025, 12, 1),
            'phone' => '24123456',
            'address' => 'Tunis, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2025, 12, 1),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '5',
            'username' => 'employee5',
            'email' => 'employee5@test.com',
            'password' => Hash::make('employee5'),
            'role' => 'employee',
            'gender' => 'male',
            'company' => 'company X',
            'job_description' => 'Administrateur Réseau',
            'start_date' => Carbon::create(2025, 12, 15),
            'phone' => '25123456',
            'address' => 'Sfax, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2025, 12, 15),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '6',
            'username' => 'employee6',
            'email' => 'employee6@test.com',
            'password' => Hash::make('employee6'),
            'role' => 'employee',
            'gender' => 'female',
            'company' => 'company X',
            'job_description' => 'Ingénieur Logiciel',
            'start_date' => Carbon::create(2026, 1, 5),
            'phone' => '26123456',
            'address' => 'Mahdia, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2026, 1, 5),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '7',
            'username' => 'employee7',
            'email' => 'employee7@test.com',
            'password' => Hash::make('employee7'),
            'role' => 'employee',
            'gender' => 'male',
            'company' => 'company X',
            'job_description' => 'Technicien Support IT',
            'start_date' => Carbon::create(2026, 1, 20),
            'phone' => '27123456',
            'address' => 'Sousse, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2026, 1, 20),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '8',
            'username' => 'employee8',
            'email' => 'employee8@test.com',
            'password' => Hash::make('employee8'),
            'role' => 'employee',
            'gender' => 'male',
            'company' => 'company X',
            'job_description' => 'Développeur Backend',
            'start_date' => Carbon::create(2026, 2, 1),
            'phone' => '28123456',
            'address' => 'Tunis, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2026, 2, 1),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '9',
            'username' => 'employee9',
            'email' => 'employee9@test.com',
            'password' => Hash::make('employee9'),
            'role' => 'employee',
            'gender' => 'female',
            'company' => 'company X',
            'job_description' => 'DevOps Engineer',
            'start_date' => Carbon::create(2026, 2, 10),
            'phone' => '29123456',
            'address' => 'Sfax, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2026, 2, 10),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => '10',
            'username' => 'employee10',
            'email' => 'employee10@test.com',
            'password' => Hash::make('employee10'),
            'role' => 'employee',
            'gender' => 'female',
            'company' => 'company X',
            'job_description' => 'Architecte Logiciel',
            'start_date' => Carbon::create(2026, 2, 28),
            'phone' => '30123456',
            'address' => 'Kairouan, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            'email_verified_at' => now(),
            'created_at' => Carbon::create(2026, 2, 28),
        ]);
    }
}
