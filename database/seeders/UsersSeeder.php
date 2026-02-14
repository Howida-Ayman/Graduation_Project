<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;
class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $now = Carbon::now();

         // Admin user
        User::updateOrCreate(
            ['national_id' => '11111111111111'],
            [
                'role_id' => 1,
                'password' => Hash::make('123456'),
                'full_name' => 'System Admin',
                'email' => 'admin@graduation.local',
                'phone' => null,
                'track_name' => null,
                'profile_image_url' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Sample Doctor
        User::updateOrCreate(
            ['national_id' => '22222222222222'],
            [
                'role_id' => 2,
                'password' => Hash::make('123456'),
                'full_name' => 'Doctor Demo',
                'email' => 'doctor@graduation.local',
                'phone' => null,
                'track_name' => 'Backend laravel',
                'profile_image_url' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Sample TA
        User::updateOrCreate(
            ['national_id' => '33333333333333'],
            [
                'role_id' =>3,
                'password' => Hash::make('123456'),
                'full_name' => 'TA Demo',
                'email' => 'ta@graduation.local',
                'phone' => '01281617411',
                'track_name' => 'Software Engineer',
                'profile_image_url' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Sample Student
        User::updateOrCreate(
            ['national_id' => '44444444444444'],
            [
                'role_id' => 4,
                'password' => Hash::make('123456'),
                'full_name' => 'Student Demo',
                'email' => 'student@graduation.local',
                'phone' => '01152915033',
                'track_name' => 'Frontend',
                'profile_image_url' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
