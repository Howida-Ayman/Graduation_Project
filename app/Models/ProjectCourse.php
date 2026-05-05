<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectCourse extends Model
{
    protected $fillable = [
        'name',
        'order',
    ];

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }

    public function defenseCommittees()
    {
        return $this->hasMany(DefenseCommittee::class);
    }

    public function milestoneCommitteeGrades()
    {
        return $this->hasMany(MilestoneCommitteeGrade::class);
    }
    public function supervisorGrades()
{
    return $this->hasMany(SupervisorGrade::class);
}
}