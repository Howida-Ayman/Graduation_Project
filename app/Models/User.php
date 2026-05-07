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

    protected $appends = ['profile_image_full_url'];
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
        'profile_image_url',
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



 // app/Models/User.php - أضيفي هذه العلاقات

public function reports()
{
    return $this->hasMany(Report::class);
}
  

public function getProfileImageFullUrlAttribute()
{
    return $this->profile_image_url ? asset($this->profile_image_url) : null;
}

    public function staffprofile()
    {
        return $this->hasOne(StaffProfile::class);
    }
    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class, 'user_id', 'id');
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

public function gradedMilestones()
{
    return $this->hasMany(TeamMilestonStatus::class, 'graded_by_user_id');
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
public function activityLogs()
{
    return $this->hasMany(ActivityLog::class);
}
public function sentAnnouncements()
{
    return $this->hasMany(Announcement::class, 'sent_by_user_id');
}
public function enrollments()
{
    return $this->hasMany(StudentEnrollment::class, 'student_user_id');
}

// Suggested Projects Favorites
public function favoriteSuggestedProjects()
{
    return $this->belongsToMany(
        SuggestedProject::class,
        'suggested_project_favorites',
        'student_user_id',
        'suggested_project_id'
    )->withTimestamps();
}

// Previous Projects Favorites
public function favoritePreviousProjects()
{
    return $this->belongsToMany(
        PreviousProject::class,
        'previous_project_favorites',
        'student_user_id',
        'previous_project_id'
    )->withTimestamps();
}

// Helper methods
public function toggleSuggestedFavorite($projectId)
{
    if ($this->favoriteSuggestedProjects()->where('suggested_project_id', $projectId)->exists()) {
        $this->favoriteSuggestedProjects()->detach($projectId);
        return false; // removed
    } else {
        $this->favoriteSuggestedProjects()->attach($projectId);
        return true; // added
    }
}

public function togglePreviousFavorite($projectId)
{
    if ($this->favoritePreviousProjects()->where('previous_project_id', $projectId)->exists()) {
        $this->favoritePreviousProjects()->detach($projectId);
        return false;
    } else {
        $this->favoritePreviousProjects()->attach($projectId);
        return true;
    }
}

// في app/Models/User.php
public function notifications()
{
    return $this->hasMany(DatabaseNotification::class, 'notifiable_id')
        ->where('notifiable_type', User::class);
}


// app/Models/User.php - أضيفي هذه العلاقات

// المحادثات اللي المستخدم مشارك فيها
public function conversations()
{
    return $this->belongsToMany(Conversation::class, 'conversation_participants', 'user_id', 'conversation_id')
        ->withPivot('role', 'joined_at', 'left_at')
        ->wherePivot('left_at', null)
        ->withTimestamps();
}

// الرسائل اللي المستخدم أرسلها
public function messages()
{
    return $this->hasMany(Message::class, 'sender_user_id');
}

// الرسائل المقروءة
public function readMessages()
{
    return $this->hasMany(MessageRead::class);
}

// طريقة جلب كل المشاركين في شات الفريق (الدكتور، المعيد، الطلاب)
public function getTeamChatParticipants($teamId)
{
    $team = Team::find($teamId);
    
    $participants = collect();
    
    // 1. أعضاء الفريق من الطلاب
    $students = $team->members()->where('status', 'active')->get();
    $participants = $participants->merge($students);
    
    // 2. المشرفين (دكتور ومعيد)
    $supervisors = $team->supervisors()->with('user')->get();
    foreach ($supervisors as $supervisor) {
        $participants->push($supervisor->user);
    }
    
    return $participants->unique('id');
}
public function isGraduated()
{
    return $this->studentProfile && $this->studentProfile->has_graduated;
}
public function isInProjectOne()
{
    return $this->enrollments()
        ->whereHas('projectCourse', function ($q) {
            $q->where('order', 1);
        })
        ->where('status', 'in_progress')
        ->exists();
}
public function isInProjectTwo()
{
    return $this->enrollments()
        ->whereHas('projectCourse', function ($q) {
            $q->where('order', 2);
        })
        ->where('status', 'in_progress')
        ->exists();
}
public function fileFeedbacks()
{
    return $this->hasMany(SubmissionFile::class, 'feedback_by_user_id');
}


}

