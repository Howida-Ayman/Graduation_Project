<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\DefenseCommittee;
use App\Models\Milestone;
use App\Models\MilestoneCommittee;
use App\Models\MilestoneCommitteeGrade;
use App\Models\MilestoneCommitteeMember;
use App\Models\ProjectCourse;
use App\Models\ProjectRule;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamSupervisor;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommitteesAndGradesSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        $rules = ProjectRule::first();

        $doctors = User::where('role_id', 2)->where('is_active', true)->get();
        $tas = User::where('role_id', 3)->where('is_active', true)->get();

        $teams = Team::where('academic_year_id', $academicYear->id)->get();
        $courses = ProjectCourse::orderBy('order')->get();

        foreach ($teams as $team) {
            $excludedSupervisorIds = TeamSupervisor::where('team_id', $team->id)
                ->whereNull('ended_at')
                ->pluck('supervisor_user_id')
                ->toArray();

            $availableDoctors = $doctors->whereNotIn('id', $excludedSupervisorIds)->values();
            $availableTas = $tas->whereNotIn('id', $excludedSupervisorIds)->values();

            if ($availableDoctors->count() < 3 || $availableTas->count() < 3) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Milestone Committee: 3 Doctors + 3 TAs
            |--------------------------------------------------------------------------
            */

            $milestoneCommittee = MilestoneCommittee::updateOrCreate(
                ['team_id' => $team->id],
                [
                    'created_by_admin_id' => User::where('role_id', 1)->first()?->id,
                ]
            );

            MilestoneCommitteeMember::where('committee_id', $milestoneCommittee->id)->delete();

            foreach ($availableDoctors->random(3) as $doctor) {
                MilestoneCommitteeMember::create([
                    'committee_id' => $milestoneCommittee->id,
                    'member_user_id' => $doctor->id,
                    'member_role' => 'doctor',
                ]);
            }

            foreach ($availableTas->random(3) as $ta) {
                MilestoneCommitteeMember::create([
                    'committee_id' => $milestoneCommittee->id,
                    'member_user_id' => $ta->id,
                    'member_role' => 'ta',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Milestone Grades
            |--------------------------------------------------------------------------
            */

            $committeeDoctors = $milestoneCommittee->members()
                ->where('member_role', 'doctor')
                ->pluck('member_user_id');

            $milestones = Milestone::where('is_active', true)->get();

            foreach ($milestones as $milestone) {
                $hasSubmission = Submission::where('team_id', $team->id)
                    ->where('milestone_id', $milestone->id)
                    ->exists();

                if (!$hasSubmission) {
                    continue;
                }

                if (rand(0, 100) < 75) {
                    MilestoneCommitteeGrade::updateOrCreate(
                        [
                            'team_id' => $team->id,
                            'milestone_id' => $milestone->id,
                            'project_course_id' => $milestone->project_course_id,
                        ],
                        [
                            'committee_id' => $milestoneCommittee->id,
                            'grade' => rand(1, (int) $milestone->max_score),
                            'graded_by_user_id' => $committeeDoctors->random(),
                            'graded_at' => now()->subDays(rand(1, 10)),
                            'notes' => fake()->sentence(12),
                        ]
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Defense Committees + Grades per Project Course
            |--------------------------------------------------------------------------
            */

            foreach ($courses as $course) {
                $teamHasCourse = $team->members()
                    ->where('status', 'active')
                    ->whereHas('user.enrollments', function ($q) use ($academicYear, $course) {
                        $q->where('academic_year_id', $academicYear->id)
                            ->where('project_course_id', $course->id)
                            ->whereIn('status', ['in_progress', 'passed']);
                    })
                    ->exists();

                if (!$teamHasCourse) {
                    continue;
                }

                $defenseCommittee = DefenseCommittee::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'project_course_id' => $course->id,
                    ],
                    [
                        'academic_year_id' => $academicYear->id,
                        'scheduled_at' => now()->addDays(rand(5, 40))->setTime(rand(9, 15), 0),
                        'location' => 'Hall ' . rand(1, 5),
                        'created_by_admin_id' => User::where('role_id', 1)->first()?->id,
                        'status' => 'scheduled',
                    ]
                );

                $defenseCommittee->members()->delete();

                foreach ($availableDoctors->random(3) as $index => $doctor) {
                    $defenseCommittee->members()->create([
                        'member_user_id' => $doctor->id,
                        'member_role' => 'doctor',
                        'seat_order' => $index + 1,
                    ]);
                }

                foreach ($availableTas->random(3) as $index => $ta) {
                    $defenseCommittee->members()->create([
                        'member_user_id' => $ta->id,
                        'member_role' => 'ta',
                        'seat_order' => $index + 1,
                    ]);
                }

                if (rand(0, 100) < 60 && method_exists($defenseCommittee, 'grade')) {
$defenseCommittee->grade()->updateOrCreate(
    ['committee_id' => $defenseCommittee->id],
    [
        'grade' => rand(1, (int) $rules?->defense_max_score),
        'graded_by_user_id' => User::where('role_id', 1)->first()?->id,
        'graded_at' => now()->subDays(rand(1, 5)),
        
    ]
);
                }
            }
        }
    }
}