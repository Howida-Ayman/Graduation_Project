<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
  protected $fillable = [
        'student_user_id',
        'academic_year_id',
        'project_course_id',
        'status',
    ];
    protected $casts = [
    'status' => 'string',
];
public function scopeInProgress($query)
{
    return $query->where('status', 'in_progress');
}

    public function student()
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
    public function projectCourse()
    {
        return $this->belongsTo(ProjectCourse::class);
    }
}
