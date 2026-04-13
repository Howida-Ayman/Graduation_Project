<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
  protected $fillable = [
        'student_user_id',
        'academic_year_id',
        'status'
    ];
    protected $casts = [
    'status' => 'string',
];
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

    public function student()
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
