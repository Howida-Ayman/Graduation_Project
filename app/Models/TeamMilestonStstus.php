<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMilestonStstus extends Model
{
        protected $table = 'team_milestone_status';

    protected $fillable = [
        'team_id',
        'milestone_id',
        'status',
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
}
