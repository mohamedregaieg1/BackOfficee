<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Création d'un seul admin
        DB::table('users')->insert([
            'first_name' => 'Ahmed',
            'last_name' => 'Daher',
            'username' => 'admin',
            'email' => 'admin@procan.tn',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'gender' => 'male',
            'company' => 'procan',
            'job_description' => 'Administrator',
            'start_date' => now(),
            'phone' => '55555555', // numéro unique
            'address' => 'Tunis, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
        ]);

        // Création des utilisateurs avec le rôle 'hr'
        DB::table('users')->insert([
            'first_name' => 'Mohamed',
            'last_name' => 'Regaieg',
            'username' => 'regaieg',
            'email' => 'mohamedregaieg54057@gmail.com',
            'password' => Hash::make('regaieg123'),
            'role' => 'hr',
            'gender' => 'male',
            'company' => 'adequate',
            'job_description' => 'HR Director',
            'start_date' => now(),
            'phone' => '51345678', // numéro unique
            'address' => 'Monastir, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Moez',
            'last_name' => 'Brahim',
            'username' => 'moez.brahim',
            'email' => 'moez.brahim@procan.tn',
            'password' => Hash::make('moez123'),
            'role' => 'hr',
            'gender' => 'male',
            'company' => 'adequate',
            'job_description' => 'HR Manager',
            'start_date' => now(),
            'phone' => '52345678', // numéro unique
            'address' => 'Tunis, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
        ]);

        // Création d'un utilisateur avec le rôle 'accountant'
        DB::table('users')->insert([
            'first_name' => 'Sofia',
            'last_name' => 'Ben Slimane',
            'username' => 'sofia.benslimane',
            'email' => 'sofia.benslimane@procan.tn',
            'password' => Hash::make('sofia123'),
            'role' => 'accountant',
            'gender' => 'female',
            'company' => 'adequate',
            'job_description' => 'Accountant',
            'start_date' => now(),
            'phone' => '53567890', // numéro unique
            'address' => 'Sfax, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            'email_verified_at' => now(),
        ]);

        // Création des utilisateurs avec le rôle 'employee' (Contenu Tunisien)
        DB::table('users')->insert([
            'first_name' => 'Omar',
            'last_name' => 'Guetat',
            'username' => 'omar',
            'email' => 'genie.om4r@gmail.com',
            'password' => Hash::make('omar123'),
            'role' => 'employee',
            'gender' => 'male',
            'company' => 'procan',
            'job_description' => 'Développeur',
            'start_date' => now(),
            'phone' => '51012345', // numéro unique
            'address' => 'Tunis, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Sami',
            'last_name' => 'Mahjoub',
            'username' => 'sami.mahjoub',
            'email' => 'sami.mahjoub@adequate.tn',
            'password' => Hash::make('sami123'),
            'role' => 'employee',
            'gender' => 'male',
            'company' => 'adequate',
            'job_description' => 'Chef de projet',
            'start_date' => now(),
            'phone' => '51123456', // numéro unique
            'address' => 'Sfax, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Imen',
            'last_name' => 'Boughzala',
            'username' => 'imen.boughzala',
            'email' => 'imen.boughzala@adequate.tn',
            'password' => Hash::make('imen123'),
            'role' => 'employee',
            'gender' => 'female',
            'company' => 'adequate',
            'job_description' => 'Responsable marketing',
            'start_date' => now(),
            'phone' => '51234567', // numéro unique
            'address' => 'Mahdia, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            'email_verified_at' => now(),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Amine',
            'last_name' => 'Touati',
            'username' => 'amine.touati',
            'email' => 'amine.touati@procan.tn',
            'password' => Hash::make('amine123'),
            'role' => 'employee',
            'gender' => 'male',
            'company' => 'procan',
            'job_description' => 'Développeur Full Stack',
            'start_date' => now(),
            'phone' => '51098765', // numéro unique
            'address' => 'Sousse, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Khaled',
            'last_name' => 'Mansouri',
            'username' => 'khaled.mansouri',
            'email' => 'khaled.mansouri@procan.tn',
            'password' => Hash::make('khaled123'),
            'role' => 'employee',
            'gender' => 'male',
            'company' => 'procan',
            'job_description' => 'Administrateur réseau',
            'start_date' => now(),
            'phone' => '51365432', // numéro unique
            'address' => 'Tunis, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Rania',
            'last_name' => 'Ben Hmida',
            'username' => 'rania.benhmida',
            'email' => 'rania.benhmida@adequate.tn',
            'password' => Hash::make('rania123'),
            'role' => 'employee',
            'gender' => 'female',
            'company' => 'adequate',
            'job_description' => 'Chargée de communication',
            'start_date' => now(),
            'phone' => '51987654', // numéro unique
            'address' => 'Sfax, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            'email_verified_at' => now(),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Zied',
            'last_name' => 'Jemai',
            'username' => 'zied.jemai',
            'email' => 'zied.jemai@procan.tn',
            'password' => Hash::make('zied123'),
            'role' => 'employee',
            'gender' => 'male',
            'company' => 'procan',
            'job_description' => 'Technicien informatique',
            'start_date' => now(),
            'phone' => '52012345', // numéro unique
            'address' => 'Kairouan, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarmale.png',
            'email_verified_at' => now(),
        ]);

        DB::table('users')->insert([
            'first_name' => 'Faten',
            'last_name' => 'Sghaier',
            'username' => 'faten.sghaier',
            'email' => 'faten.sghaier@adequate.tn',
            'password' => Hash::make('faten123'),
            'role' => 'employee',
            'gender' => 'female',
            'company' => 'adequate',
            'job_description' => 'Assistante RH',
            'start_date' => now(),
            'phone' => '51498765', // numéro unique
            'address' => 'Tunis, Tunisia',
            'avatar_path' => 'http://127.0.0.1:8000/dist/img/avatarfemale.png',
            'email_verified_at' => now(),
        ]);
    }
}
