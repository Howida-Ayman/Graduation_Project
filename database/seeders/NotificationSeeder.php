<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\AcademicYear;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first academic year or create one
        $academicYear = AcademicYear::first();
        
        if (!$academicYear) {
            $academicYear = AcademicYear::create([
                'name' => '2024/2025',
                'is_active' => true,
            ]);
        }

        // Get users (students or supervisors)
        $users = User::take(5)->get(); // First 5 users

        if ($users->isEmpty()) {
            // If no users exist, create a test user
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
            $users = collect([$user]);
        }

        // Different notification types
        $notificationTypes = [
            'team_join_request' => [
                'message' => 'sent a request to join your team',
                'icon' => 'user-plus',
                'color' => 'blue',
            ],
            'team_disbanded' => [
                'message' => 'your team has been disbanded',
                'icon' => 'users',
                'color' => 'red',
            ],
            'proposal_submitted' => [
                'message' => 'submitted a new project proposal',
                'icon' => 'file',
                'color' => 'green',
            ],
            'grade_added' => [
                'message' => 'a new grade has been added for you',
                'icon' => 'star',
                'color' => 'yellow',
            ],
            'proposal_status_changed' => [
                'message' => 'your proposal status has been changed',
                'icon' => 'refresh-cw',
                'color' => 'purple',
            ],
            'milestone_note' => [
                'message' => 'added a note on your milestone',
                'icon' => 'clipboard',
                'color' => 'indigo',
            ],
            'supervision_request' => [
                'message' => 'requested to be your supervisor',
                'icon' => 'user-check',
                'color' => 'emerald',
            ],
        ];

        // Create 10 fake notifications per user
        foreach (range(1, 10) as $i) {
            $type = array_rand($notificationTypes);
            $notificationData = $notificationTypes[$type];
            $userNames = ['Ahmed', 'Mona', 'Omar', 'Sara', 'Khaled', 'Nour', 'Youssef', 'Laila'];
            $randomName = $userNames[array_rand($userNames)];
            
            foreach ($users as $user) {
                \App\Models\DatabaseNotification::create([
                    'id' => (string) Str::uuid(),
                    'academic_year_id' => $academicYear->id,
                    'type' => $type,
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => $type,
                        'message' => $randomName . ' ' . $notificationData['message'],
                        'icon' => $notificationData['icon'],
                        'color' => $notificationData['color'],
                        'created_at' => now()->subMinutes(rand(1, 10080))->toISOString(),
                    ]),
                    'read_at' => rand(0, 1) ? now()->subDays(rand(1, 5)) : null,
                    'created_at' => now()->subMinutes(rand(1, 10080)),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}