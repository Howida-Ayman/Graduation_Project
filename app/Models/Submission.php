<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    //
     protected $fillable = [
        'milestone_requirement_id',
        'team_id',
        'submitted_by_user_id',
        'notes',
        'graded_by_user_id',
        'score',
        'feedback',
        'graded_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'graded_at' => 'datetime',
    ];

    public function requirement()
    {
        return $this->belongsTo(MilestoneRequirement::class, 'milestone_requirement_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by_user_id');
    }

    

}
