<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'national_id',
        'password',
        'full_name',
        'email',
        'phone',
        'track_name',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function staffprofile()
    {
        return $this->hasOne(StaffProfile::class);
    }
    public function studentprofile()
    {
        return $this->hasOne(StudentProfile::class);
    }

public function role()
{
    return $this->belongsTo(Role::class);
}

// في app/Models/User.php
public function supervisedTeams()
{
    return $this->hasMany(TeamSupervisor::class, 'supervisor_user_id');
}

    // التسليمات اللي المستخدم قدمها
public function submissions()
{
    return $this->hasMany(Submission::class, 'submitted_by_user_id');
}

// التسليمات اللي المستخدم صححها
public function gradedSubmissions()
{
    return $this->hasMany(Submission::class, 'graded_by_user_id');
}

public function teamMemberships()
    {
        return $this->hasMany(TeamMembership::class, 'student_user_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_memberships', 'student_user_id', 'team_id')
            ->withPivot('role_in_team', 'status', 'joined_at', 'left_at')
            ->wherePivot('status', 'active');
    }

    public function currentTeam()
    {
        return $this->teams()->first();
    }

    public function isInTeam(): bool
    {
        return $this->teamMemberships()
            ->where('status', 'active')
            ->exists();
    }

    public function isTeamLeader(): bool
    {
        $membership = $this->teamMemberships()
            ->where('status', 'active')
            ->first();
        
        if (!$membership) {
            return false;
        }
        
        $team = $membership->team;
        return $team && $team->leader_user_id == $this->id;
    }

    public function sentRequests()
    {
        return $this->hasMany(Request::class, 'from_user_id');
    }

    public function receivedRequests()
    {
        return $this->hasMany(Request::class, 'to_user_id');
    }
public function createdDefenseCommittees()
{
    return $this->hasMany(DefenseCommittee::class, 'created_by_admin_id');
}

public function defenseCommitteeMemberships()
{
    return $this->hasMany(DefenseCommitteeMember::class, 'member_user_id');
}

public function enteredDefenseGrades()
{
    return $this->hasMany(DefenseGrade::class, 'entered_by_user_id');
}


}
