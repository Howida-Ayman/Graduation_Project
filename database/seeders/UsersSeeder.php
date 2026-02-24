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

        // ============================================
        // 1. Admin user (موجود)
        // ============================================
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

        // ============================================
        // 2. Doctors (المشرفين من الدكاترة)
        // ============================================
        $doctors = [
            [
                'national_id' => '22222222222222',
                'full_name' => 'Doctor Demo',
                'email' => 'doctor@graduation.local',
                'track_name' => 'Backend Laravel',
            ],
            [
                'national_id' => '55555555555555',
                'full_name' => 'Dr. Ahmed El-Nagar',
                'email' => 'ahmed.nagar@staff.com',
                'track_name' => 'Artificial Intelligence',
            ],
            [
                'national_id' => '66666666666666',
                'full_name' => 'Dr. Mona Hassan',
                'email' => 'mona.hassan@staff.com',
                'track_name' => 'Software Engineering',
            ],
            [
                'national_id' => '77777777777777',
                'full_name' => 'Dr. Khaled Omar',
                'email' => 'khaled.omar@staff.com',
                'track_name' => 'Data Science',
            ],
            [
                'national_id' => '88888888888888',
                'full_name' => 'Dr. Sarah Ahmed',
                'email' => 'sarah.ahmed@staff.com',
                'track_name' => 'Mobile Development',
            ],
            [
                'national_id' => '99999999999999',
                'full_name' => 'Dr. Mahmoud Samir',
                'email' => 'mahmoud.samir@staff.com',
                'track_name' => 'Cloud Computing',
            ],
        ];

        foreach ($doctors as $doctor) {
            User::updateOrCreate(
                ['national_id' => $doctor['national_id']],
                [
                    'role_id' => 2, // doctor role
                    'password' => Hash::make('123456'),
                    'full_name' => $doctor['full_name'],
                    'email' => $doctor['email'],
                    'phone' => '010' . rand(10000000, 99999999),
                    'track_name' => $doctor['track_name'],
                    'profile_image_url' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // ============================================
        // 3. TAs (المعيدين)
        // ============================================
        $tas = [
            [
                'national_id' => '33333333333333',
                'full_name' => 'TA Demo',
                'email' => 'ta@graduation.local',
                'track_name' => 'Software Engineer',
            ],
            [
                'national_id' => '10101010101010',
                'full_name' => 'Ahmed Fayez',
                'email' => 'ahmed.fayez@staff.com',
                'track_name' => 'Web Development',
            ],
            [
                'national_id' => '11111111111112',
                'full_name' => 'Mohamed Gayed',
                'email' => 'mohamed.gayed@staff.com',
                'track_name' => 'Mobile Development',
            ],
            [
                'national_id' => '12121212121212',
                'full_name' => 'Sara Hassan',
                'email' => 'sara.hassan@staff.com',
                'track_name' => 'Database Administration',
            ],
            [
                'national_id' => '13131313131313',
                'full_name' => 'Nadia Ali',
                'email' => 'nadia.ali@staff.com',
                'track_name' => 'UI/UX Design',
            ],
            [
                'national_id' => '14141414141414',
                'full_name' => 'Omar Mahmoud',
                'email' => 'omar.mahmoud@staff.com',
                'track_name' => 'Network Security',
            ],
        ];

        foreach ($tas as $ta) {
            User::updateOrCreate(
                ['national_id' => $ta['national_id']],
                [
                    'role_id' => 3, // TA role
                    'password' => Hash::make('123456'),
                    'full_name' => $ta['full_name'],
                    'email' => $ta['email'],
                    'phone' => '011' . rand(10000000, 99999999),
                    'track_name' => $ta['track_name'],
                    'profile_image_url' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // ============================================
        // 4. Students (الطلاب) - الأهم
        // ============================================
        $students = [
            // Student Demo (موجود)
            [
                'national_id' => '44444444444444',
                'full_name' => 'Student Demo',
                'email' => 'student@graduation.local',
                'track_name' => 'Frontend',
            ],
            
            // الفريق الأول (CS)
            [
                'national_id' => '15151515151515',
                'full_name' => 'Mohamed Ali',
                'email' => 'mohamed.ali@student.com',
                'track_name' => 'Backend Development',
            ],
            [
                'national_id' => '16161616161616',
                'full_name' => 'Yara Tarek',
                'email' => 'yara.tarek@student.com',
                'track_name' => 'Frontend Development',
            ],
            [
                'national_id' => '17171717171717',
                'full_name' => 'Farida Khaled',
                'email' => 'farida.khaled@student.com',
                'track_name' => 'Mobile Development',
            ],
            
            // الفريق الثاني (IT)
            [
                'national_id' => '18181818181818',
                'full_name' => 'Shahd Mostafa',
                'email' => 'shahd.mostafa@student.com',
                'track_name' => 'Full Stack Development',
            ],
            [
                'national_id' => '19191919191919',
                'full_name' => 'Ahmed Kamal',
                'email' => 'ahmed.kamal@student.com',
                'track_name' => 'DevOps',
            ],
            [
                'national_id' => '20202020202020',
                'full_name' => 'Rana Saleh',
                'email' => 'rana.saleh@student.com',
                'track_name' => 'UI/UX Design',
            ],
            
            // الفريق الثالث (IS)
            [
                'national_id' => '21212121212121',
                'full_name' => 'Omar Hassan',
                'email' => 'omar.hassan@student.com',
                'track_name' => 'Data Science',
            ],
            [
                'national_id' => '22222222222223',
                'full_name' => 'Nour Ahmed',
                'email' => 'nour.ahmed@student.com',
                'track_name' => 'Artificial Intelligence',
            ],
            [
                'national_id' => '23232323232323',
                'full_name' => 'Hossam Eldin',
                'email' => 'hossam.eldin@student.com',
                'track_name' => 'Cloud Computing',
            ],
            
            // الفريق الرابع (MM)
            [
                'national_id' => '24242424242424',
                'full_name' => 'Mariam Gamal',
                'email' => 'mariam.gamal@student.com',
                'track_name' => 'Graphic Design',
            ],
            [
                'national_id' => '25252525252525',
                'full_name' => 'Ali Youssef',
                'email' => 'ali.youssef@student.com',
                'track_name' => 'Game Development',
            ],
            [
                'national_id' => '26262626262626',
                'full_name' => 'Salma Ashraf',
                'email' => 'salma.ashraf@student.com',
                'track_name' => 'Animation',
            ],
            
            // طلاب إضافيين
            [
                'national_id' => '27272727272727',
                'full_name' => 'Khaled Mahmoud',
                'email' => 'khaled.mahmoud@student.com',
                'track_name' => 'Cybersecurity',
            ],
            [
                'national_id' => '28282828282828',
                'full_name' => 'Diaa El-Din',
                'email' => 'diaa.eldin@student.com',
                'track_name' => 'Network Administration',
            ],
            [
                'national_id' => '29292929292929',
                'full_name' => 'Heba Karim',
                'email' => 'heba.karim@student.com',
                'track_name' => 'Database Management',
            ],
            [
                'national_id' => '30303030303030',
                'full_name' => 'Mostafa Ibrahim',
                'email' => 'mostafa.ibrahim@student.com',
                'track_name' => 'Software Testing',
            ],
        ];

        foreach ($students as $student) {
            User::updateOrCreate(
                ['national_id' => $student['national_id']],
                [
                    'role_id' => 4, // student role
                    'password' => Hash::make('123456'),
                    'full_name' => $student['full_name'],
                    'email' => $student['email'],
                    'phone' => '012' . rand(10000000, 99999999),
                    'track_name' => $student['track_name'],
                    'profile_image_url' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}