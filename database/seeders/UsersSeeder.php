<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        /*
        =========================
        ADMIN
        =========================
        */

        User::updateOrCreate(
            ['national_id' => '11111111111111'],
            [
                'role_id' => 1,
                'full_name' => 'System Admin',
                'email' => 'admin@gp.com',
                'phone' => '01000000000',
                'password' => Hash::make('123456'),
                'is_active' => true,
            ]
        );

        /*
        =========================
        DOCTORS
        =========================
        */

        for ($i = 1; $i <= 8; $i++) {

            User::updateOrCreate(
                ['national_id' => '2222222222222' . $i],
                [
                    'role_id' => 2,
                    'full_name' => "Doctor {$i}",
                    'email' => "doctor{$i}@gp.com",
                    'phone' => "0101111111{$i}",
                    'password' => Hash::make('123456'),
                    'is_active' => true,
                ]
            );
        }

        /*
        =========================
        TEACHING ASSISTANTS
        =========================
        */

        for ($i = 1; $i <= 8; $i++) {

            User::updateOrCreate(
                ['national_id' => '3333333333333' . $i],
                [
                    'role_id' => 3,
                    'full_name' => "TA {$i}",
                    'email' => "ta{$i}@gp.com",
                    'phone' => "0102222222{$i}",
                    'password' => Hash::make('123456'),
                    'is_active' => true,
                ]
            );
        }

        /*
        =========================
        PROJECT 1 STUDENTS
        =========================
        */

        for ($i = 10; $i <= 35; $i++) {

            User::updateOrCreate(
                ['national_id' => '444444444444' . $i],
                [
                    'role_id' => 4,

                    'full_name' => "Project1 Student {$i}",

                    'email' => "p1student{$i}@gp.com",

                    'phone' => "0103333333{$i}",

                    'track_name' => fake()->randomElement([
                        'AI',
                        'Cyber Security',
                        'Software Engineering',
                        'Information Systems'
                    ]),

                    'password' => Hash::make('123456'),

                    'is_active' => true,
                ]
            );
        }

        /*
        =========================
        PROJECT 2 STUDENTS
        =========================
        */

        for ($i = 10; $i <= 35; $i++) {

            User::updateOrCreate(
                ['national_id' => '555555555555' . $i],
                [
                    'role_id' => 4,

                    'full_name' => "Project2 Student {$i}",

                    'email' => "p2student{$i}@gp.com",

                    'phone' => "0104444444{$i}",

                    'track_name' => fake()->randomElement([
                        'AI',
                        'Cyber Security',
                        'Software Engineering',
                        'Information Systems'
                    ]),

                    'password' => Hash::make('123456'),

                    'is_active' => true,
                ]
            );
        }
    }
}