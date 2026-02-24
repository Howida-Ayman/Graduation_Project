<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    //
    protected $fillable = [
        'id',
        'academic_year_id',
        'department_id',
        'leader_user_id'
        

    ];



    public function academicYear()
{
    return $this->belongsTo(AcademicYear::class);
}
public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function members()
    {
        return $this->hasMany(TeamMembership::class);
    }

    // ✅ العلاقة الجديدة مع المشرفين
    public function supervisors()
    {
        return $this->belongsToMany(
            User::class,
            'team_supervisors',
            'team_id',
            'supervisor_user_id'
        )->withPivot(['supervisor_role', 'assigned_at', 'ended_at'])
         ->wherePivot('ended_at', null);  // المشرفين الحاليين بس
    }

    // المشرفين الحاليين
    public function currentSupervisors()
    {
        return $this->supervisors()->wherePivot('ended_at', null);
    }

    // المشرف الرئيسي (لو عايزة تجيبيه)
    public function mainSupervisor()
    {
        return $this->belongsToMany(
            User::class,
            'team_supervisors',
            'team_id',
            'supervisor_user_id'
        )->withPivot('supervisor_role')
         ->wherePivot('supervisor_role', 'doctor')
         ->wherePivot('ended_at', null);
    }





}
