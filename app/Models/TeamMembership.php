<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMembership extends Model
{
    protected $fillable = [
        'team_id',
        'student_user_id',
        'role_in_team', // leader/member
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
public function student()
{
    return $this->belongsTo(User::class, 'student_user_id');
}

public function user()
{
    return $this->student();
}
}
