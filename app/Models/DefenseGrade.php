<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefenseGrade extends Model
{
    protected $table='defense_grades';
     protected $fillable = [
        'committee_id',
        'project_course_id',
        'grade',
        'graded_by_user_id',
        'graded_at',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
        'graded_at' => 'datetime',
    ];

public function team()
{
    return $this->belongsTo(Team::class);
}

public function committee()
{
    return $this->belongsTo(DefenseCommittee::class);
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
