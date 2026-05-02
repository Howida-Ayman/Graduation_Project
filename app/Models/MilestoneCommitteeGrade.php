<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilestoneCommitteeGrade extends Model
{
    protected $fillable = [
        'milestone_id',
        'team_id',
        'committee_id',
        'project_course_id',
        'grade',
        'graded_by_user_id',
        'graded_at',
        'notes',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
        'graded_at' => 'datetime',
    ];

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function committee()
    {
        return $this->belongsTo(MilestoneCommittee::class, 'committee_id');
    }

    public function projectCourse()
    {
        return $this->belongsTo(ProjectCourse::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by_user_id');
    }
}