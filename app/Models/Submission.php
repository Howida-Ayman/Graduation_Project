<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    //
     protected $fillable = [
        'milestone_id',
        'team_id',
        'submitted_by_user_id',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
    'submitted_at' => 'datetime',
];

   public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    
    public function files()
    {
    return $this->hasMany(SubmissionFile::class);
    }
}
