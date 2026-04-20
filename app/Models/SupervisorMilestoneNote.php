<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisorMilestoneNote extends Model
{
    protected $fillable = [
        'academic_year_id',
        'milestone_id',
        'supervisor_user_id',
        'note',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }
}