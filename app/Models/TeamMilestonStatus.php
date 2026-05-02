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
    'status_updated_at',
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
   public function submissions()
{
    return $this->hasMany(Submission::class, 'milestone_id', 'milestone_id')
        ->where('team_id', $this->team_id);
}
public function isDelayed()
{
    return $this->status === 'delayed';
}
}
