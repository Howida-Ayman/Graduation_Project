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
    return $this->hasMany(TeamMembership::class)
        ->where('status', 'active'); // 👈 أضيفي شرط النشاط
}

public function activeMembers()
{
    return $this->hasMany(TeamMembership::class)
        ->where('status', 'active');
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

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function activeProposal()
    {
        return $this->hasOne(Proposal::class)
            ->whereIn('status', ['approved', 'completed'])
           ->latest();
    }

    // في app/Models/Team.php
    public function teamSupervisors()
    {
    return $this->hasMany(TeamSupervisor::class);
    }
    // العلاقة مع submissions
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
    // العلاقة مع team_milestone_status
    public function teamMilestonestatus()
    {
        return $this->hasMany(TeamMilestonStatus::class);
    }
    // علاقة مباشرة مع milestones (through pivot)
    public function milestones()
    {   
    return $this->belongsToMany(Milestone::class, 'team_milestone_status')
        ->withPivot(['status', 'milestone_grade', 'graded_by_user_id', 'graded_at'])
        ->withTimestamps();
    }

    public function graduationProject()
    {
        return $this->hasOne(GraduationProject::class, 'team_id');
    }
    public function defenseCommittee()
    {
    return $this->hasOne(DefenseCommittee::class, 'team_id');
    }
    public function meetings()
    {
    return $this->hasMany(Meeting::class);
    }
    public function activityLogs()
    {
    return $this->hasMany(ActivityLog::class);
    }
    public function announcements()
    {  
    return $this->hasMany(Announcement::class);
    }
    public function scopeactiveYear($query)
{
    $activeYear = AcademicYear::where('is_active', true)->first();
    return $query->where('academic_year_id', $activeYear->id);
}


// app/Models/Team.php - أضيفي هذه العلاقات

public function conversation()
{
    return $this->hasOne(Conversation::class);
}


// الحصول على كل مستخدمي الفريق (طلاب + مشرفين)
public function getAllUsers()
{
    $userIds = collect();
    
    // الطلاب
    $userIds = $userIds->merge(
        $this->members()->where('status', 'active')->pluck('student_user_id')
    );
    
    // المشرفين
    $userIds = $userIds->merge(
        $this->supervisors()->pluck('supervisor_user_id')
    );
    
    return User::whereIn('id', $userIds)->get();
}

}
