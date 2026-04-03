<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMilestonStatus extends Model
{
        protected $table = 'team_milestone_status';

    protected $fillable = [
        'team_id',
        'milestone_id',
        'status',
        'milestone_grade',
        'graded_by_user_id',
        'graded_at' 
    ];
     protected $casts = [
    'milestone_grade' => 'decimal:2',
    'graded_at' => 'datetime',
];

    // بيربط بالتيم
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // بيربط بالمايلستون
    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }
    public function grader()
    {
    return $this->belongsTo(User::class, 'graded_by_user_id');
    }
    public function submissions()
    {
    return $this->hasMany(Submission::class, 'milestone_id', 'milestone_id')
        ->whereColumn('team_id', 'team_milestone_status.team_id');
    }
}
